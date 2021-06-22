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
use Symfony\Component\HttpFoundation\Response;

trait DriverTrait
{
    /**
     * @param  Contracts  $contract
     *
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
     * @param  Contracts  $contract
     *
     * @return string Сообщение
     */
    public function sendPolice(Contracts $contract): string
    {
        $data = new stdClass();
        $data->receiver = $contract->subject_fullname;
        $data->insurRules = null;
        $nsEmail = new Email($data);
        $file = $this->getFilePolice($contract);
        if (!is_array($file)) {
            $file = [$file];
        }

        foreach ($file as $item) {
            $nsEmail->attach(
                $item,
                [
                    'as' => 'Полис.pdf',
                    'mime' => 'application/pdf',
                ]
            );
        }

        try {
            Mail::to($contract->subjectValue['email'])->send($nsEmail);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param  Contracts  $contract
     *
     * @return string
     */
    protected function getFilePolice(Contracts $contract)
    {
        $filename = config('mortgage.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';
        $filenameWithPath = public_path() . '/' . $filename;
        if (!file_exists($filenameWithPath)) {
            $this->printPolicy($contract, false, true, $filenameWithPath);
        }

        return $filenameWithPath;
    }

    /**
     * @param  Contracts  $contract
     *
     * @return string
     */
    public static function gefaultFileName(Contracts $contract)
    {
        return config('mortgage.pdf.path') . sha1($contract->id . $contract->number) . '.pdf';
    }

    /**
     * @param  Contracts  $contract
     * @param  string     $filenameWithPath
     *
     * @return bool
     */
    protected function isFilePoliceExitst(Contracts $contract, &$filenameWithPath = ''): bool
    {
        if (!$filenameWithPath) {
            $filename = self::gefaultFileName($contract);
            $filenameWithPath = public_path() . '/' . $filename;
        }

        return file_exists(public_path() . '/' . $filenameWithPath);
    }

    /**
     * @param  Contracts  $contract
     * @param             $objectId
     *
     * @return string
     */
    protected static function createFilePath(Contracts $contract, $objectId)
    {
        $filePathObject = self::gefaultFileName($contract);
        $filePathObjectArray = explode('.', $filePathObject);
        $ext = array_pop($filePathObjectArray);
        array_push($filePathObjectArray, $objectId, $ext);
        $filePathObject = implode('.', $filePathObjectArray);

        return $filePathObject;
    }

    /**
     * @param  Contracts  $contract
     * @param  PayLinks   $links
     *
     * @return PayLink
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getPayLink(Contracts $contract, PayLinks $links): PayLink
    {
        $invoiceNum = sprintf(
            '%s%03d%06d/%s',
            'NS',
            $contract->company_id,
            $contract->id,
            Carbon::now()->format('His')
        );

        if (in_array(config('app.env'), ['local', 'testing'])) {
            $invoiceNum = time() % 100 . $invoiceNum;
        }

        $data = [
            'successUrl' => config('mortgage.str_host') . $links->getSuccessUrl(),
            'failUrl' => config('mortgage.str_host') . $links->getFailUrl(),
            'phone' => str_replace([' ', '-'], '', $contract['subject']['phone']),
            'fullName' => $contract['subject_fullname'],
            'passport' => $contract['subject_passport'],
            'name' => "Полис по Ипотека №{$contract->id}",
            'description' => "Оплата за полис {$contract->company->name} №{$contract->id}",
            'amount' => $contract['premium'],
            'merchantOrderNumber' => $invoiceNum,
        ];
        Log::info(__METHOD__ . '. Data for acquiring', [$data]);
        $response = app()->make(PayService::class)->getPayLink($data);

        if (isset($response->errorCode) && $response->errorCode !== 0) {
            throw new Exception(
                $response->errorMessage . ' (code: ' . $response->errorCode . ')',
                Response::HTTP_NOT_ACCEPTABLE
            );
        }

        return new PayLink($response->orderId, $response->formUrl, $invoiceNum);
    }

    /**
     * @param  Contracts  $contract
     * @param  bool  $sample
     * @param  bool  $reset
     * @param  string|null  $filePath
     *
     * @return string|array
     */
    public abstract function printPolicy(
        Contracts $contract,
        bool $sample,
        bool $reset,
        ?string $filePath = null
    );
}
