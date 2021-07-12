<?php

namespace App\Services;

use App\Exceptions\Services\PaymentServiceException;
use App\Models\Contract;
use App\Models\Payment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class PaymentService
 * @package App\Services
 */
class PaymentService
{
    const BANK_CODE = 'Sber';
    const STATUS_MAP = [
        'Paid' => Contract::STATUS_CONFIRMED,
        'Pending' => Contract::STATUS_DRAFT,
        'Cancel' => 0,
    ];

    /**
     * @var string
     */
    protected string $host;

    /**
     * PaymentService constructor.
     * @param string $host
     */
    public function __construct(string $host)
    {
        $this->host = $host;
    }

    /**
     * @param Contract $contract
     * @param array $urls
     * @return array
     * @throws PaymentServiceException
     */
    public function payLink(Contract $contract, array $urls) : array
    {
        $data = [
            'bankCode' => self::BANK_CODE,
            'successUrl' => Arr::get($urls, 'success'),
            'failUrl' => Arr::get($urls, 'fail'),
            'totalAmount' => $contract->premium,
            'customerDetails' => [
                'phone' => str_replace('-', '', Arr::get($contract->subject->value, 'phone')),
                'email' => Arr::get($contract->subject->value, 'email'),
                'fullName' => $contract->subjectFullname,
            ],
            'items' => [
                [
                    'id' => $contract->id,
                    'name' => sprintf('Оплата за полис "%s"', $contract->program->programName),
                    'code' => 1,
                    'measure' => 'полис',
                    'quantity' => 1,
                    'price' => $contract->premium,
                    'ofdDetails' => [
                        'supplierName' => $contract->company->name,
                        'supplierInn' => $contract->company->inn,
                    ]
                ]
            ]
        ];
        $resp = Arr::get(
            $this->request('POST', '/v1/orders', ['json' => $data], __METHOD__),
            'data'
        );

        $this->flushPayment($contract, Arr::get($resp, 'invoiceNum'), Arr::get($resp, 'orderId'));

        return [
            'url' => Arr::get($resp, 'payLink'),
            'orderId' => Arr::get($resp, 'orderId'),
            'invoiceNum' => Arr::get($resp, 'invoiceNum'),
        ];
    }

    /**
     * @param Payment $payment
     * @return int
     * @throws PaymentServiceException
     */
    public function orderStatus(Payment $payment): int
    {
        $status = Arr::get(
            $this->request('GET', "/v1/orders/{$payment->orderId}/status", [], __METHOD__),
            'data.status'
        );

        return Arr::get(self::STATUS_MAP, $status, 0);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param string|null $context
     * @return array
     * @throws PaymentServiceException
     */
    protected function request(string $method, string $uri, array $options, ?string $context) : array
    {
        try {
            Log::debug($context . " request to {$uri}", $options);
            $resp = (new Client(['base_uri' => $this->host]))
                ->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            if ($e instanceof RequestException && $e->hasResponse()) {
                Log::error(
                    "{$context} ошибка запроса: {$e->getResponse()->getStatusCode()}",
                    [json_decode((string)$e->getResponse()->getBody(), true)]
                );
            }
            throw new PaymentServiceException($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return json_decode((string) $resp->getBody(), true, 128);
    }

    /**
     * @param Contract $contract
     * @param string $invoiceNum
     * @param string $orderId
     * @return Payment
     */
    protected function flushPayment(Contract $contract, string $invoiceNum, string $orderId): Payment
    {
        $payment = Payment::updateOrCreate(
            ['contract_id' => $contract->id],
            ['invoice_num' => $invoiceNum, 'order_id' => $orderId]
        );
        $payment->contract()->associate($contract);
        $payment->save();

        return $payment;
    }
}
