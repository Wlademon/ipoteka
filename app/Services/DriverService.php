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
            throw new DriverServiceException(
                "Driver {$driver} not found", Response::HTTP_NOT_FOUND
            );
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
            Log::error($throwable->getMessage());
            throw new DriverServiceException('При подсчете произошла ошибка.');
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
            throw new DriverServiceException(
                "Дата начала полиса не может быть позже чем дата заключения (сегодня) + {$maxStartDateSelection}",
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
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
            //dd($result);
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
            Log::error($throwable->getMessage());
            if ($throwable instanceof DriverExceptionInterface) {
                throw new $throwable;
            }

            throw new DriverServiceException(
                'При создании полиса возникла ошибка.', Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
            throw new DriverServiceException(
                'Невозможно сгенерировать полис, т.к. полис в статусе "ожидание оплаты"',
                HttpResponse::HTTP_NOT_ACCEPTABLE
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
            Log::error($throwable->getMessage());
            throw new DriverServiceException('При получении бланка полиса произошла ошибка.');
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
            $driver = $this->getDriverByCode($contract->program->company->code);
            if ($driver->sendPolice($contract)) {
                return ['message' => 'Email was sent to '.$contract->subject_value['email']];
            }

            throw new DriverServiceException(
                'Email was not sent to '.$contract->subject_value['email'],
            );
        }

        throw new DriverServiceException('Status of Contract is not Confirmed');
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
            throw new DriverServiceException($throwable->getMessage());
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
        $res = Helper::getPolicyNumber($params);
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
            throw new DriverServiceException($throwable->getMessage());
        }
    }
}
