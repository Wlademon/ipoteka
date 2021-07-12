<?php

namespace App\Services;

use App\Drivers\DriverInterface;
use App\Mail\Email;
use App\Models\Contract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class MailPoliceService
 * @package App\Services
 */
class MailPoliceService
{
    /**
     * @var Contract
     */
    protected $contract;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param Contract $contract
     * @param DriverInterface $driver
     * @param false $isRebuild
     * @return bool
     */
    public function send(Contract $contract, DriverInterface $driver, $isRebuild = false)
    {
        $this->contract = $contract;
        $this->driver = $driver;
        $filePath = $this->getFilePath();
        try {
            if ($isRebuild || !file_exists($filePath)) {
                $this->generateFile($filePath);
            }

            Mail::to('wladoseid@gmail.com')->send($this->buildMessage($filePath));

            Log::info("Mail sent to userEmail={$contract->subject_value['email']} contractId={$contract->id}");
        } catch (\Exception $e) {
            Log::warning(
                "Cant send email to userEmail={$contract->subject_value['email']} contractId={$contract->id}",
                [$e->getMessage()]
            );
            return false;
        }

        return true;
    }

    /**
     * @param $file
     * @return Email
     */
    protected function buildMessage($file): Email
    {
        $data = new \stdClass();
        $data->receiver = $this->contract->subject_fullname;
        $data->insurRules = null;
        $email = new Email($data);
        $email->attach(
            $file,
            [
                'as' => 'Полис.pdf',
                'mime' => 'application/pdf',
            ]
        );

        return $email;
    }

    /**
     * @param string $filePath
     */
    protected function generateFile(string $filePath)
    {
        $this->driver->printPolicy($this->contract, false, true, $filePath);
    }

    /**
     * @return string
     */
    protected function getFilePath(): string
    {
        $contract = $this->contract;
        $filename = config('ns.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';

        return public_path() . '/' . $filename;
    }
}

