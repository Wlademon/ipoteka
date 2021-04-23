<?php

namespace App\Drivers\Traits;

use App\Mail\Email;
use App\Models\Contracts;
use Exception;
use Illuminate\Support\Facades\Mail;
use stdClass;

trait DriverTrait
{
    /**
     * @param Contracts $contract
     * @return array
     */
    public function getStatus(Contracts $contract): array
    {
        $status = 'undefined';
        if (isset($contract->status)) {
            if ($contract->status == Contracts::STATUS_DRAFT) {
                $status = 'Draft';
            } elseif ($contract->status == Contracts::STATUS_CONFIRMED) {
                $status = 'Confirmed';
            }
        }

        return ['status' => $status];
    }

    /**
     * Отправка полиса на почту
     *
     * @param Contracts $contract
     * @return string Сообщение
     */
    public function sendPolice(Contracts $contract): string
    {
        $data = new stdClass();
        $data->receiver = $contract->subject_fullname;
        $data->insurRules = null;
        $nsEmail = new Email($data);
        $nsEmail->attach(
            $this->getFilePolice($contract),
            [
                'as' => 'Полис.pdf',
                'mime' => 'application/pdf',
            ]
        );
        try {
            Mail::to($contract->subjectValue['email'])->send($nsEmail);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param Contracts $contract
     * @return string
     */
    protected function getFilePolice(Contracts $contract)
    {
        $filename = config('ns.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';
        $filenameWithPath = public_path() . '/' . $filename;
        if (!file_exists($filenameWithPath)) {
            $this->printPolicy($contract, false, true, $filenameWithPath);
        }

        return $filenameWithPath;
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @param bool $reset
     * @param string|null $filePath
     * @return string
     */
    public abstract function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null): string;
}
