<?php

namespace App\Services;

use App;
use App\Drivers\DriverInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\LocalDriverInterface;
use App\Drivers\LocalPaymentDriverInterface;
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
use Strahovka\Payment\PayService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Class DriverService
 *
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
     *
     * @throws Exception
     */
    protected function setDriver(string $driver = null): void
    {
        $driverCode = trim(strtolower($driver));
        $this->driver = App::make($driverCode);

        if (!$this->driver) {
            throw (new DriverServiceException(
                "Driver {$driver} not found", Response::HTTP_NOT_FOUND
            ))->addLogData(
                __METHOD__
            );
        }
    }

    /**
     * @param  string  $code
     * @param  bool    $reset
     *
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
     * @param  PayLinks   $links
     *
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
     *
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
            throw (new DriverServiceException(
                'При подсчете произошла ошибка.', Response::HTTP_NOT_ACCEPTABLE
            ))->addLogData(
                __METHOD__,
                $throwable->getMessage()
            );
        }
    }

    /**
     * @param  Program  $program
     * @param  array    $data
     *
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
            throw (new DriverServiceException(
                "Дата начала полиса не может быть позже чем дата заключения (сегодня) + {$maxStartDateSelection} дней",
                Response::HTTP_UNPROCESSABLE_ENTITY
            ))->addLogData(
                __METHOD__
            );
        }
    }

    /**
     * @param  array  $data
     *
     * @return array
     * @throws DriverServiceException
     */
    public function savePolicy(array $data): array
    {
        DB::beginTransaction();
        $model = new Contracts();
        $model->fill($data);
        $program = Program::whereProgramCode($data['programCode'])->with('company')->firstOrFail();
        try {
            $result = $this->getDriverByCode($program->company->code)->createPolicy($model, $data);
        } catch (Throwable $throwable) {
            throw (new DriverServiceException(
                'При получении полиса возникла ошибка.', Response::HTTP_NOT_ACCEPTABLE
            ))->addLogData(
                __METHOD__,
                $throwable->getMessage(),
                $throwable->getCode()
            );
        }
        try {
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
            throw (new DriverServiceException(
                'При создании полиса возникла ошибка.', Response::HTTP_INTERNAL_SERVER_ERROR
            ))->addLogData(
                __METHOD__,
                $throwable->getMessage(),
                $throwable->getCode()
            );
        }
        $result->setContractId($model->ext_id);

        return array_merge(['id' => $model->id], $result->toArray());
    }

    /**
     * @param  Collection  $collection
     * @param  string      $type
     *
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
     * @param  Contracts    $contract
     * @param  bool         $sample
     * @param  bool         $reset
     * @param  string|null  $filePath
     *
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
            throw (new DriverServiceException(
                'Невозможно сгенерировать полис, т.к. полис в статусе "ожидание оплаты"',
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            ))->addLogData(
                __METHOD__
            );
        }
        try {
            return $this->getDriverByCode($contract->program->company->code)->printPolicy(
                $contract,
                $sample,
                $reset,
                $filePath
            );
        } catch (Throwable $throwable) {
            if ($throwable instanceof DriverExceptionInterface) {
                $code = HttpResponse::HTTP_NOT_ACCEPTABLE;
            } else {
                $code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
            }
            throw (new DriverServiceException(
                'При получении бланка полиса произошла ошибка.', $code
            ))->addLogData(
                __METHOD__,
                $throwable->getMessage(),
                $throwable->getCode()
            );
        }
    }

    /**
     * @param  Contracts  $contract
     *
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
                    return ['message' => 'Email was sent to ' . $contract->subject_value['email']];
                }
            } catch (Throwable $t) {
                if ($t instanceof DriverExceptionInterface) {
                    $code = HttpResponse::HTTP_NOT_ACCEPTABLE;
                }
                throw (new DriverServiceException(
                    'Email was not sent to ' . $contract->subject_value['email'], $code
                ))->addLogData(__METHOD__, $t->getMessage(), $t->getCode());
            }
        }

        $code = HttpResponse::HTTP_UNPROCESSABLE_ENTITY;
        $message = 'Невозможно сгенерировать полис, т.к. полис в статусе "ожидание оплаты"';
        throw (new DriverServiceException($message, $code))->addLogData(__METHOD__);
    }

    /**
     * @param  Contracts  $contract
     *
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
            if ($throwable->getCode() === HttpResponse::HTTP_UNPROCESSABLE_ENTITY) {
                $code = HttpResponse::HTTP_UNPROCESSABLE_ENTITY;
            }
            throw (new DriverServiceException(
                'Ошибка при подтверждении платежа.', $code
            ))->addLogData(
                __METHOD__,
                $throwable->getMessage(),
                $throwable->getCode()
            );
        }
    }

    /**
     * @param  Contracts  $contract
     *
     * @return array
     * @internal param Contracts $contract
     */
    public function acceptPayment(Contracts $contract, PayService $payService, string $orderId): array
    {
        $company = $contract->company;
        try {
            $driver = $this->getDriverByCode($company->code);
            if ($driver instanceof LocalPaymentDriverInterface) {
                Log::info("Start check payment status with OrderID: {$orderId}");
                $status = $payService->getOrderStatus($orderId);
                Log::info("Status: {$status['status']}");
                if (empty($status['isPayed'])) {
                    throw new DriverServiceException('Полис не оплачен.');
                }
                $contract->status = Contracts::STATUS_CONFIRMED;
            }
            if ($driver instanceof LocalDriverInterface) {
                $params = [
                    'product_code' => 'mortgage',
                    'program_code' => $contract->program->programCode,
                    'bso_owner_code' => $company->code,
                    'bso_receiver_code' => 'STRAHOVKA',
                    'count' => 1,
                ];
                Log::info(__METHOD__ . ". getPolicyNumber with params:", [$params]);
                $res = Helper::getPolicyNumber($params);
                $contract->objects->first()->setAttribute('number', $res->data->bso_numbers[0])->save();
            }

        } catch (Throwable $throwable) {
            throw (new DriverServiceException(
                'Ошибка при подтверждении платежа.', HttpResponse::HTTP_BAD_REQUEST
            ))->addLogData(
                __METHOD__,
                $throwable->getMessage(),
                $throwable->getCode()
            );
        }

        $this->statusConfirmed($contract);
        $contract->save();
        Log::info("Contract with ID {$contract->id} was saved.");

        return [
            'id' => $contract['id'],
            'contractId' => $contract['ext_id'],
            'premium' => $contract->premium,
            'subject' => [
                'email' => $contract->subject_value['email'],
            ],
        ];
    }

    /**
     * @param  Contracts  $contract
     *
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

            throw (new DriverServiceException('Ошибка при получении статуса.', $code))->addLogData(
                __METHOD__,
                $throwable->getMessage(),
                $throwable->getCode()
            );
        }
    }
}
