<?php

namespace App\Services;

use App\Drivers\DriverInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\InnerDriver;
use App\Drivers\ReninsDriver;
use App\Drivers\ResoDriver;
use App\Drivers\Traits\LoggerTrait;
use App\Exceptions\Services\DriverServiceException;
use App\Helpers\Helper;
use App\Mail\Email;
use App\Models\Contracts;
use App\Models\Programs;
use App\Services\PayService\PayLinks;
use Carbon\Carbon;
use Exception;
use http\Exception\RuntimeException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use stdClass;
use Strahovka\Payment\PayService;

/**
 * Class DriverService
 * @package App\Services
 */
class DriverService
{
    use LoggerTrait;

    /**
     * @todo Переделать
     */
    const DRIVERS = [

    ];

    /**
     * @var DriverInterface|null
     */
    private ?DriverInterface $driver = null;

    /**
     * @param string|null $driver
     * @throws Exception
     */
    protected function setDriver(string $driver = null): void
    {
        $driverIdentifier = trim(strtolower($driver));

        $driver = self::DRIVERS[$driverIdentifier] ?? null;
        if (!$driver) {
            self::abortLog(
                "Driver {$driverIdentifier} not found",
                DriverServiceException::class,
                Response::HTTP_NOT_FOUND
            );
        }

        $this->driver = new $driver;
    }

    /**
     * @param string $code
     * @param bool $reset
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
     * @param Contracts $contract
     * @param PayLinks $links
     * @return PayLinkInterface
     */
    public function getPayLink(Contracts $contract, PayLinks $links): PayLinkInterface
    {
        return $this->getDriverByCode($contract->program->company->code)->getPayLink($contract, $links);
    }

    /**
     * @param $data
     * @return Arrayable
     * @throws Exception
     */
    public function calculate($data): Arrayable
    {
        $program = Programs::whereProgramCode($data['programCode'])->with('company')->firstOrFail();
        $this->minStartValidator($program, $data);
        return $this->getDriverByCode($program->company->code)->calculate($data);
    }

    /**
     * @param Programs $program
     * @param array $data
     * @throws Exception
     */
    protected function minStartValidator(Programs $program, array $data): void
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
            self::abortLog(
                "Дата начала полиса не может быть позже чем дата заключения (сегодня) + {$maxStartDateSelection}",
                DriverServiceException::class,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws DriverServiceException
     */
    public function savePolicy(array $data): array
    {
        try {
            $program = Programs::whereProgramCode($data['programCode'])->with('company')->firstOrFail();
            return $this->getDriverByCode($program->company->code)->createPolicy($data);
        } catch (\Throwable $throwable) {
            self::abortLog($throwable->getMessage(), DriverServiceException::class);
        }
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @param bool $reset
     * @param string|null $filePath
     * @return string
     * @throws DriverServiceException|RuntimeException
     */
    public function printPdf(Contracts $contract, bool $sample, bool $reset = false, ?string $filePath = null): string
    {
        if (!$sample && $contract->status !== Contracts::STATUS_CONFIRMED) {
            self::abortLog('Невозможно сгенерировать полис, т.к. полис в статусе "ожидание оплаты"', RuntimeException::class);
        }
        try {
            return $this->getDriverByCode($contract->program->company->code)->printPolicy($contract, $sample, $reset, $filePath);
        } catch (\Throwable $throwable) {
            self::abortLog($throwable->getMessage(), DriverServiceException::class);
        }
    }

    /**
     * @param Contracts $contract
     * @return array
     * @throws DriverServiceException
     */
    public function sendMail(Contracts $contract): array
    {
        if ($contract->status == Contracts::STATUS_CONFIRMED) {
            $driver = $this->getDriverByCode($contract->program->company->code);
            if ((new MailPoliceService())->send($contract, $driver, true)) {
                return ['message' => 'Email was sent to ' . $contract->subject_value['email']];
            }

            self::abortLog('Email was not sent to ' . $contract->subject_value['email'], DriverServiceException::class);
        }

        self::abortLog('Status of Contract is not Confirmed', DriverServiceException::class);
    }

    /**
     * @param Contracts $contract
     * @throws DriverServiceException
     */
    public function statusConfirmed(Contracts $contract): void
    {
        try {
            $this->getDriverByCode($contract->program->company->code)->payAccept($contract);
        } catch (\Throwable $throwable) {
            self::abortLog($throwable->getMessage(), DriverServiceException::class);
        }
    }

    /**
     * @todo Поправить
     * @param Contracts $contract
     * @return array
     * @internal param Contracts $contract
     */
    public function acceptPayment(Contracts $contract): array
    {
        if ($contract->status != Contracts::STATUS_DRAFT) {
            self::abortLog('Полис не в статусе "Ожидает оплаты"', DriverServiceException::class, Response::HTTP_PAYMENT_REQUIRED);
        }
        $company = $contract->company;
        $contract->status = Contracts::STATUS_CONFIRMED;
        $params = [
            'product_code' => 'telemed',
            'program_code' => $contract->program->programCode,
            'bso_owner_code' => $company->code,
            'bso_receiver_code' => $contract->owner_code, // Код получателя БСО'bso_receiver_code' => 'STRAHOVKA'
            "count" => 1,
        ];
        Log::info(__METHOD__ . ". getPolicyNumber with params:", [$params]);
        $res = Helper::getPolicyNumber($params);
        $contract->number = $res->data->bso_numbers[0];
        $contract->save();

        $resUwin = Helper::getUwinContractId($contract);
        if ($resUwin) {
            $contract->uwContractId = isset($resUwin->contractId) ? $resUwin->contractId : null;
        }
        $contract->save();
        $this->statusConfirmed($contract);
        self::log("Contract with ID {$contract->id} was saved.");

        return [
            'id' => $contract['id'],
            'subject' => [
                'email' => $contract->subject_value['email'],
            ],
        ];
    }

    /**
     * @param Contracts $contract
     * @return array
     * @throws DriverServiceException
     */
    public function getStatus(Contracts $contract): array
    {
        try {
            return $this->getDriverByCode($contract->program->company->code)->getStatus($contract);
        } catch (\Throwable $throwable) {
            self::abortLog($throwable->getMessage(), DriverServiceException::class);
        }
    }
}
