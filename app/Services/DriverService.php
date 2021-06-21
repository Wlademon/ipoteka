<?php

namespace App\Services;

use App;
use App\Drivers\DriverInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Exceptions\Drivers\DriverExceptionInterface;
use App\Exceptions\Services\DriverServiceException;
use App\Helpers\Helper;
use App\Models\Contracts;
use App\Models\Objects;
use App\Models\Program;
use App\Models\Subject;
use App\Services\PayService\PayLinks;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Class DriverService
 * @package App\Services
 */
class DriverService
{
    /**
     * @var DriverInterface|null
     */
    private ?DriverInterface $driver = null;

    /**
     * @param  string|null  $driver
     * @throws Exception
     */
    protected function setDriver(string $driver = null): void
    {
        $driverCode = trim(strtolower($driver));
        $this->driver = App::make($driverCode);

        if (!$this->driver) {
            $message = "Driver {$driver} not found";
            $code = Response::HTTP_NOT_FOUND;
            Log::error(self::class.'::'.__METHOD__.' '.$message."(code: $code)");
            throw new DriverServiceException($message, $code);
        }
    }

    /**
     * @param  string  $code
     * @param  bool  $reset
     * @return DriverInterface
     * @throws Exception
     */
    protected function getDriverByCode(string $code, bool $reset = false): DriverInterface
    {
        $actualCode = trim(strtolower($code));
        if (!$this->driver || $reset) {
            $this->setDriver($actualCode);
        }

        return $this->driver;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver(): ?DriverInterface
    {
        return $this->driver;
    }

    /**
     * @param  Contracts  $contract
     * @param  PayLinks  $links
     * @return PayLinkInterface
     */
    public function getPayLink(Contracts $contract, PayLinks $links): PayLinkInterface
    {
        return $this->getDriverByCode($contract->program->company->code)->getPayLink(
            $contract,
            $links
        );
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    public function calculate($data): array
    {
        try {
            $program = Program::whereProgramCode($data['programCode'])
                              ->with('company')
                              ->firstOrFail();
            $this->minStartValidator($program, $data);

            return $this->getDriverByCode($program->company->code)->calculate($data)->toArray();
        } catch (Throwable $throwable) {
            $message = $throwable->getMessage();
            $code = Response::HTTP_NOT_ACCEPTABLE;
            Log::error(self::class.'::'.__METHOD__.' '.$message."(code: $code)");
            throw new DriverServiceException('При подсчете произошла ошибка.', $code);
        }
    }

    /**
     * @param  Program  $program
     * @param  array  $data
     * @throws Exception
     */
    protected function minStartValidator(Program $program, array $data): void
    {
        $maxStartDateSelection = $program->conditions->maxStartDateSelection ?? '3m';
        preg_match("/([0-9]+)([dmy]+)/", $maxStartDateSelection, $maxSds);
        $startDate = Carbon::now()->startOfDay();
        switch ($maxSds[2]) {
            case 'd':
                $validActiveFromMax = $startDate->addDays($maxSds[1]);
                break;
            case 'm':
                $validActiveFromMax = $startDate->addMonths($maxSds[1]);
                break;
            case 'y':
                $validActiveFromMax = $startDate->addYears($maxSds[1]);
                break;
            default:
                $validActiveFromMax = $startDate->addMonths(3);
        }
        $activeFrom = Carbon::parse($data['activeFrom']);
        if ($activeFrom > $validActiveFromMax) {
            $message = "Дата начала полиса не может быть позже чем дата заключения (сегодня) + {$maxStartDateSelection}";
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
            Log::error(self::class.'::'.__METHOD__.' '.$message."(code: $code)");
            throw new DriverServiceException($message, $code);
        }
    }

    /**
     * @param  array  $data
     * @return array
     * @throws DriverServiceException
     */
    public function savePolicy(array $data): array
    {
        try {
            DB::beginTransaction();
            $model = new Contracts();
            $model->fill($data);
            $program = Program::whereProgramCode($data['programCode'])
                              ->with('company')
                              ->firstOrFail();
            $result = $this->getDriverByCode($program->company->code)->createPolicy($model, $data);

            $policeData = collect($data);
            $objects = collect($policeData->only(['objects'])->get('objects'));
            $model->premium = $result->getPremiumSum();
            $model->saveOrFail();
            $subject = (new Subject())->fill(['value' => $policeData->get('subject')]);
            $subject->contract()->associate($model);
            $subject->saveOrFail();
            $objectLife = $this->getObjectModel($objects, 'life');
            if ($objectLife && $result->getLifePremium()) {
                $objectLife->contract()->associate($model);
                $objectLife->loadFromDriverResult($result);
                $objectLife->saveOrFail();
            }
            $objectProp = $this->getObjectModel($objects, 'property');
            if ($objectProp && $result->getPropertyPremium()) {
                $objectProp->contract()->associate($model);
                $objectProp->loadFromDriverResult($result);
                $objectProp->saveOrFail();
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            if ($throwable instanceof DriverExceptionInterface) {
                $codeError = Response::HTTP_NOT_ACCEPTABLE;
            } else {
                $codeError = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            Log::error(
                self::class.'::'.__METHOD__.' '.$throwable->getMessage()." (code: $codeError)"
            );
            throw new DriverServiceException('При создании полиса возникла ошибка.', $codeError);
        }
        $result->setContractId($model->id);

        return $result->toArray();
    }

    /**
     * @param  Collection  $collection
     * @param  string  $type
     * @return Objects|null
     */
    protected function getObjectModel(Collection $collection, string $type): ?Objects
    {
        $object = $collection->get($type);
        if (!$object) {
            return $object;
        }
        $model = new Objects();
        $model->product = $type;
        $model->value = $object;

        return $model;
    }

    /**
     * @param  Contracts  $contract
     * @param  bool  $sample
     * @param  bool  $reset
     * @param  string|null  $filePath
     * @return string|array
     * @throws DriverServiceException
     */
    public function printPdf(
        Contracts $contract,
        bool $sample,
        bool $reset = false,
        ?string $filePath = null
    ) {
        $this->getStatus($contract);
        if (!$sample && $contract->status !== Contracts::STATUS_CONFIRMED) {
            $code = HttpResponse::HTTP_UNPROCESSABLE_ENTITY;
            $message = 'Невозможно сгенерировать полис, т.к. полис в статусе "ожидание оплаты"';
            Log::error(
                self::class.'::'.__METHOD__. ' ' . $message . "(code: $code)"
            );
            throw new DriverServiceException($message, $code);
        }
        try {
            return $this->getDriverByCode($contract->program->company->code)->printPolicy(
                $contract,
                $sample,
                $reset,
                $filePath
            );
        } catch (Throwable $throwable) {
            $code = $throwable->getCode();
            Log::error(self::class.'::'.__METHOD__.' '.$throwable->getMessage()."(code: $code)");
            if ($throwable instanceof DriverExceptionInterface) {
                $code = HttpResponse::HTTP_NOT_ACCEPTABLE;
            } else {
                $code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            }
            throw new DriverServiceException(
                'При получении бланка полиса произошла ошибка.', $code
            );
        }
    }

    /**
     * @param  Contracts  $contract
     * @return array
     * @throws DriverServiceException
     */
    public function sendMail(Contracts $contract): array
    {
        if ($contract->status == Contracts::STATUS_CONFIRMED) {
            $code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            try {
                $driver = $this->getDriverByCode($contract->program->company->code);
                if ($driver->sendPolice($contract)) {
                    return ['message' => 'Email was sent to '.$contract->subject_value['email']];
                }
            } catch (Throwable $t) {
                Log::error(
                    self::class.'::'.__METHOD__." {$t->getMessage()}",
                    [$t->getTraceAsString()]
                );
                if ($t instanceof DriverExceptionInterface) {
                    $code = HttpResponse::HTTP_NOT_ACCEPTABLE;
                }
            }
            Log::error(
                self::class.'::'.__METHOD__.' Email was not sent to '.
                $contract->subject_value['email']
            );
            throw new DriverServiceException(
                'Email was not sent to '.$contract->subject_value['email'], $code
            );
        }

        $code = HttpResponse::HTTP_UNPROCESSABLE_ENTITY;
        $message = 'Status of Contract is not Confirmed';
        Log::error(
            self::class.'::'.__METHOD__ . " $message (code: {$code})"
        );
        throw new DriverServiceException($message, $code);
    }

    /**
     * @param  Contracts  $contract
     * @throws DriverServiceException
     */
    public function statusConfirmed(Contracts $contract): void
    {
        try {
            $this->getDriverByCode($contract->program->company->code)->payAccept($contract);
        } catch (Throwable $throwable) {
            $code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            if ($throwable instanceof DriverExceptionInterface) {
                $code = HttpResponse::HTTP_NOT_ACCEPTABLE;
            }
            Log::error(
                self::class.'::'.__METHOD__.' '.$throwable->getMessage()." (code: $code.)"
            );
            throw new DriverServiceException('Ошибка при подтверждении платежа.', $code);
        }
    }

    /**
     * @param  Contracts  $contract
     * @return array
     * @internal param Contracts $contract
     */
    public function acceptPayment(Contracts $contract): array
    {
        if ($contract->status != Contracts::STATUS_DRAFT) {
            throw new DriverServiceException(
                'Полис не в статусе "Ожидает оплаты"', Response::HTTP_PAYMENT_REQUIRED
            );
        }
        $company = $contract->company;
        $contract->status = Contracts::STATUS_CONFIRMED;
        $params = [
            'product_code' => 'mortgage',
            'program_code' => $contract->program->programCode,
            'bso_owner_code' => $company->code,
            'bso_receiver_code' => 'STRAHOVKA',
            'count' => 1,
        ];
        Log::info(__METHOD__.". getPolicyNumber with params:", [$params]);
        try {
            $res = Helper::getPolicyNumber($params);
        } catch (Throwable $throwable) {
            Log::error(
                self::class.'::'.__METHOD__.$throwable->getMessage().
                " (code: {$throwable->getCode()})"
            );
            throw new DriverServiceException(
                'Ошибка при получении номера полиса.', HttpResponse::HTTP_BAD_REQUEST
            );
        }

        $contract->objects->first()->setAttribute('number', $res->data->bso_numbers[0])->save();
        $contract->save();
        $this->statusConfirmed($contract);
        Log::info("Contract with ID {$contract->id} was saved.");

        return [
            'id' => $contract['id'],
            'subject' => [
                'email' => $contract->subject_value['email'],
            ],
        ];
    }

    /**
     * @param  Contracts  $contract
     * @return array
     * @throws DriverServiceException
     */
    public function getStatus(Contracts $contract): array
    {
        try {
            return $this->getDriverByCode($contract->program->company->code)->getStatus($contract);
        } catch (Throwable $throwable) {
            $code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            if ($throwable instanceof DriverExceptionInterface) {
                $code = HttpResponse::HTTP_NOT_ACCEPTABLE;
            }
            Log::error(
                self::class.'::'.__METHOD__.' '.$throwable->getMessage().
                " (code: {$throwable->getCode()})"
            );

            throw new DriverServiceException('Ошибка при получении статуса.', $code);
        }
    }
}
