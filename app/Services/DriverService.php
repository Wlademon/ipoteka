<?php

namespace App\Services;

use App\Drivers\DriverInterface;
use App\Drivers\InnerDriver;
use App\Drivers\ReninsDriver;
use App\Drivers\ResoDriver;
use App\Drivers\Traits\LoggerTrait;
use App\Exceptions\Services\DriverServiceException;
use App\Helpers\Helper;
use App\Mail\Email;
use App\Models\Contracts;
use App\Models\Programs;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use stdClass;
use Strahovka\Payment\PayService;

class DriverService
{
    use LoggerTrait;

    const DRIVERS = [
        'reso_garantija' => ResoDriver::class,
        'rensins' => ReninsDriver::class,
        'vsk' => InnerDriver::class,
        'alfa_msk' => InnerDriver::class,
    ];

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


    public function getPayLink(PayService $service, Contracts $contract, Request $request)
    {
        return $this->getDriverByCode($contract->program->company->code)->getPayLink($service, $contract, $request);
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function calculate($data)
    {
        if ($data['activeFrom'] > $data['activeTo']) {
            self::abortLog(
                'Дата окончания полиса раньше даты начала',
                DriverServiceException::class,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $program = Programs::whereProgramCode($data['programCode'])->with('company')->first();
        if (!$program) {
            self::abortLog(
                "Program not found with code {$data['programCode']}",
                DriverServiceException::class,
                Response::HTTP_NOT_FOUND
            );
        }
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

        return $this->getDriverByCode($program->company->code)->calculate($data);
    }

    public function savePolicy(Request $data): array
    {
        $program = Programs::whereProgramCode($data['programCode'])->with('company')->first();
        if (!$program) {
            self::abortLog("Program not found with code {$data['programCode']}", DriverServiceException::class, Response::HTTP_NOT_FOUND);
        }

        return $this->getDriverByCode($program->company->code)->createPolicy($data);
    }

    public function printPdf(Contracts $contract, bool $sample, bool $reset = false, ?string $filePath = null): string
    {
        return $this->getDriverByCode($contract->program->company->code)
                    ->printPolicy($contract, $sample, $reset, $filePath);
    }

    /**
     * @param Contracts $contract
     * @return array
     * @throws Exception
     */
    public function sendMail(Contracts $contract): array
    {
        if ($contract->status == Contracts::STATUS_CONFIRMED) {
            if ($this->sendPolicy($contract, true)) {
                return ['message' => 'Email was sent to ' . $contract->subject_value['email']];
            }

            self::abortLog('Email was not sent to ' . $contract->subject_value['email'], DriverServiceException::class, Response::HTTP_BAD_REQUEST);
        }

        self::abortLog('Status of Contract is not Confirmed', DriverServiceException::class, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param Contracts $contract
     * @param bool $generatePdf force generate PDF file
     * @return bool
     */
    protected function sendPolicy(Contracts $contract, $generatePdf = false): bool
    {
        $data = new stdClass();
        $data->receiver = $contract->subject_fullname;
        $data->insurRules = $contract->program->conditions->insurRules ?? null;
        $nsEmail = new Email($data);
        $filename = config('ns.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';
        $filenameWithPath = public_path() . '/' . $filename;
        if ($generatePdf || !file_exists($filenameWithPath)) {
            $this->printPdf($contract, false, $generatePdf, $filenameWithPath);
        }

        $nsEmail->attach(
            $filenameWithPath,
            [
                'as' => 'Полис.pdf',
                'mime' => 'application/pdf',
            ]
        );

        try {
            Mail::to($contract->subjectValue['email'])->send($nsEmail);
            self::log("Mail sent to userEmail={$contract->subject_value['email']} contractId={$contract->id}");
        } catch (Exception $e) {
            self::warning(
                "Cant send email to userEmail={$contract->subject_value['email']} contractId={$contract->id}",
                [$e->getMessage()]
            );
            return false;
        }

        return true;
    }

    public function statusConfirmed(Contracts $contract): void
    {
        $this->getDriverByCode($contract->program->company->code)->statusConfirmed($contract);
    }

    /**
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

    public function triggerGetLink(Contracts $contract): void
    {
        $this->getDriverByCode($contract->program->company->code)->triggerGetLink($contract);
    }

    /**
     * @param Contracts $contract
     * @return array|null
     */
    public function getStatus(Contracts $contract): array
    {
        return $this->getDriverByCode($contract->program->company->code)->getStatus($contract);
    }
}
