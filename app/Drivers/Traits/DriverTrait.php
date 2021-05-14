<?php

namespace App\Drivers\Traits;

use App\Drivers\DriverResults\PayLink;
use App\Mail\Email;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use stdClass;
use Strahovka\Payment\PayService;

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

    public function gefaultFileName(Contracts $contract)
    {
        return config('ns.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';
    }

    protected function isFilePoliceExitst(Contracts $contract, &$filenameWithPath = ''): bool
    {
        $filename = config('ns.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';
        $filenameWithPath = public_path() . '/' . $filename;

        return file_exists($filenameWithPath);
    }

    public function getPayLink(Contracts $contract, PayLinks $links): PayLink
    {
        $invoiceNum = sprintf("%s%03d%06d/%s", 'NS', $contract->company_id, $contract->id, Carbon::now()->format('His'));

        if (in_array(env('APP_ENV'), ['local', 'testing'])) {
            $invoiceNum = time() % 100 . $invoiceNum;
        }

        $data = [
            'successUrl' => env('STR_HOST', 'https://strahovka.ru') . $links->getSuccessUrl(),
            'failUrl' => env('STR_HOST', 'https://strahovka.ru') . $links->getFailUrl(),
            'phone' => str_replace([' ', '-'], '', $contract['subject']['phone']),
            'fullName' => $contract['subject_fullname'],
            'passport' => $contract['subject_passport'],
            'name' => "Полис по Телемед №{$contract->id}",
            'description' => "Оплата за полис {$contract->company->name} №{$contract->id}",
            'amount' => $contract['premium'],
            'merchantOrderNumber' => $invoiceNum,
        ];
        Log::info(__METHOD__ . '. Data for acquiring', [$data]);
        $response = app()->make(PayService::class)->getPayLink($data);

        if (isset($response->errorCode) && $response->errorCode !== 0) {
            throw new \Exception($response->errorMessage . ' (code: ' . $response->errorCode . ')', 500);
        }

        return new PayLink($response->orderId, $response->formUrl, $invoiceNum);
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
