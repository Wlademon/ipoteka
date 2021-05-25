<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLink;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Services\MerchantServices;
use App\Drivers\Services\RegisterUserProfile;
use App\Drivers\Source\Alpha\AlfaAuth;
use App\Drivers\Source\Alpha\AlphaCalculator;
use App\Drivers\Traits\DriverTrait;
use App\Drivers\Traits\LoggerTrait;
use App\Exceptions\Drivers\AlphaException;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;
use Carbon\Carbon;
use Carbon\Traits\Creator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Class AbsolutDriver
 * @package App\Drivers
 */
class AlfaMskDriver implements DriverInterface
{
    use DriverTrait {
        DriverTrait::getStatus as getTStatus;
    }
    use LoggerTrait;

    const STATUS_DRAFT = 1;
    const STATUS_CONFIRMED = 2;
    const ISSUE_SUCCESSFUL = 'ISSUE_SUCCESSFUL';
    const POST_POLICY_URL = '/msrv/mortgage/partner/calc';
    const POST_POLICY_CREATE_URL = '/msrv/mortgage/partner/calcAndSave';
    const GET_POLICY_STATUS_URL = '/msrv/mortgage/partner/contractStatus';
    const POST_PAYMENT_RECEIPT = '/msrv/payment/receipt/common';

    protected string $host;
    protected Client $client;
    protected int $managerId = 0;

    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->client = new Client();
        throw_if(
            !$repository->get($prefix . 'host', false),
            new AlphaException('Not set host property')
        );
        $this->host = $repository->get($prefix . 'host');
    }

    /**
     * @inheritDoc
     * @throws AlphaException
     */
    public function calculate(array $data): CalculatedInterface
    {
        $auth = new AlfaAuth();
        $authToken = $auth->getToken($this->client)['access_token'];

        $calculator = $this->collectData($data);

        try {
            $result = $this->client->post(
                $this->host . self::POST_POLICY_URL, [
                    'headers' => [
                        'Authorization' => "Bearer {$authToken}"
                    ],
                    'json' => $calculator->getData()
                ]
            );
        } catch (\Throwable $e) {
            dd($e->getResponse()->getBody()->getContents());
        }

        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error calc');
        }
        $decodeResult = json_decode($result->getBody()->getContents(), true);

        return new Calculated(
            null,
            Arr::get($decodeResult, 'lifePremium', 0),
            Arr::get($decodeResult, 'propertyPremium', 0)
        );
    }

    protected function collectData(array $data): AlphaCalculator
    {
        $dataCollect = collect($data);
        $calculator = new AlphaCalculator();
        $calculator->setBank($dataCollect->get('mortgageeBank'), $dataCollect->get('remainingDebt'));
        $calculator->setCalcDate($dataCollect->get('activeFrom'));

        $objects = collect($dataCollect->get('objects'));

        if ($objects->has('life')) {
            $life = collect($objects->get('life'));

            $calculator->setInsurant($life->get('gender'), $life->get('birthDate'));
            $calculator->setLifeRisk($life->get('professions', []), $life->get('sports', []));
        }
        if ($objects->has('property')) {
            $property = collect($objects->get('property'));
            $calculator->setInsurance();
            $calculator->setPropertyRisk(
                'Москва',
                $property->get('isWooden', false),
                $property->get('buildYear')
            );
        }

        return $calculator;
    }


    /**
     * @throws \Throwable
     */
    protected function createSingleAccount($authToken, $contractList): array
    {
        try {
            $result = $this->client->post(
                $this->host . self::POST_PAYMENT_RECEIPT, [
                    'headers' => [
                        'Authorization' => "Bearer {$authToken}"
                    ],
                    'json' => [
                        'contractList' => $contractList
                    ]
                ]
            );
        } catch (\Throwable $e) {
            self::abortLog($e->getMessage(), AlphaException::class);
        }

        throw_if($result->getStatusCode() !== 200, new AlphaException('Error create payment'));
        $decodeResult = json_decode($result->getBody()->getContents(), true);
        $text = ' Оплата страхового полиса ';
        return [
            Arr::get($decodeResult, 'id', 0),
            Arr::get($decodeResult, 'number', 0),
        ];
    }

    protected function getIsOperDocument(): string // заглушка
    {
        return 'y';
    }


    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {
        $contractOptions = $contract->getOptionsAttribute();
        $contractOptions['singleAccount'] = '';
        $contractOptions['orderId'] = '';

        $auth = new AlfaAuth();
        $authToken = $auth->getToken($this->client)['access_token'];

        $registerOrder = new MerchantServices();
        $singleAcc = $this->createSingleAccount(
            $authToken,
            $contract->getOptionsAttribute()['contractList']);
        $contractOptions['singleAccount'] = $singleAcc[0];
        $registerOrder->setDescription($singleAcc);
        $registerOrder->setMerchantOrderNumber($singleAcc[0]);

        $registerOrder->setExpirationDate(Carbon::now()->addMinutes(20)->format('Y-m-d\TH:i:sP'));
        $registerOrder->setIsOperDocument($this->getIsOperDocument());
        $registerOrder->setReturnUrl(url($payLinks->getSuccessUrl()));
        $registerOrder->setFailUrl(url($payLinks->getFailUrl()));

        $response = $registerOrder->registerOrder();

        throw_if(empty($response->get('orderId')), new AlphaException('Missing orderId'));

        $contractOptions['orderId'] = $response->get('orderId');
        $contract->setOptionsAttribute($contractOptions);
        $contract->save();

        return new PayLink(
            $response->get('orderId'),
            $response->get('formUrl'),
            $contract->remainingDebt
        );
    }

    /**
     * @inheritDoc
     */

    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {
        $contractOptions = $contract->getOptionsAttribute();
        $contractOptions['contractList'] = [];
        $auth = new AlfaAuth();
        $authToken = $auth->getToken($this->client)['access_token'];

        $dataCollect = collect($data);
        $policy = $this->CollectData($data);

        $subject = collect($dataCollect->get('subject'));
        $property = collect(Arr::get($data, 'objects.property'));

        $policy->setInsurerAddress($subject->get('city'), $subject->get('street'));
        $policy->setInsurerEmail($subject->get('email'));
        $policy->setInsurerFullName($subject->get('firstName'), $subject->get('lastName'), $subject->get('middleName'));
        $policy->setInsurerPersonDocument(
            (new Carbon($subject->get('docIssueDate')))->format('Y-m-d'),
            $subject->get('docNumber'),
            $subject->get('docSeries')
        );
        $policy->setInsurerPhone($subject->get('phone'));
        if ($property->isNotEmpty()) {
            $policy->setAddress($property->only(['city', 'state', 'street', 'house', 'block', 'apartment'])->join(','));
            $policy->setAddressSquare($property->get('area'));
        }
        $policy->setDateCreditDoc((new Carbon())->format('Y-m-d'));
        $policy->setNumberCreditDoc($dataCollect->get('mortgageAgreementNumber')); //?

        $decodePostResult = $this->getDataFromCreatePolicy($authToken, $policy);
        $contractOptions['upid'] = Arr::get($decodePostResult, 'upid');
        $decodeGetResult = $this->getStatusContract($authToken, Arr::get($decodePostResult, 'upid'), 'Error get data from createPolicy');

        if (Arr::has($decodeGetResult['propertyContract'], 'contractId')) {
            $contractOptions['contractList'][] = Arr::get($decodeGetResult['propertyContract'], 'contractId', 0);
        }
        if (Arr::has($decodeGetResult['lifeContract'], 'contractId')) {
            $contractOptions['contractList'][] = Arr::get($decodeGetResult['lifeContract'], 'contractId', 0);
        }
        $contract->setOptionsAttribute($contractOptions);
        $contract->save();

        return new CreatedPolicy(
            $contract->id,
            Arr::get($decodeGetResult['lifeContract'], 'contractId', 0),
            Arr::get($decodeGetResult['propertyContract'], 'contractId', 0),
            Arr::get($decodeGetResult, 'lifePremium', 0),
            Arr::get($decodeGetResult, 'propertyPremium', 0),
            null,
            null
        );
    }

    /**
     * @param $authToken
     * @param $policy
     * @return array
     * @throws AlphaException
     */
    protected function getDataFromCreatePolicy(string $authToken, object $policy): array
    {
        try {
            $postResult = $this->client->post(
                $this->host . self::POST_POLICY_CREATE_URL, [
                    'headers' => [
                        'Authorization' => "Bearer {$authToken}"
                    ],
                    'json' => $policy->getData()
                ]
            );
        } catch (\Throwable $e) {
            self::abortLog($e->getMessage(), AlphaException::class);
        }

        if ($postResult->getStatusCode() !== 200) {
            throw new AlphaException('Error create policy');
        }
        $decodePostResult = json_decode($postResult->getBody()->getContents(), true);

        if (!Arr::has($decodePostResult, 'upid')) {
            throw new AlphaException('Response has not upid');
        }

        return $decodePostResult;
    }

    protected function getStatusContract($authToken, $upid, $message)
    {
        var_dump(json_encode([
            'headers' => [
                'Authorization' => "Bearer {$authToken}"
            ],
            'query' => [
                'upid' => $upid
            ]]));
        $i = 0;
        do {
            $i++;
            try {
                $getResult = $this->client->get(
                    $this->host . self::GET_POLICY_STATUS_URL, [
                        'headers' => [
                            'Authorization' => "Bearer {$authToken}"
                        ],
                        'query' => [
                            'upid' => $upid
                        ]
                    ]
                );
            } catch (\Throwable $e) {
                self::abortLog($e->getMessage(), AlphaException::class);
            }
            $response = json_decode($getResult->getBody()->getContents(), true);
            $contracts = Arr::only($response, ['lifeContract', 'propertyContract']);
            $contractIds = array_filter(Arr::pluck($contracts, 'contractId'));
            usleep(500000);
        } while (
            (count($contracts) !== count($contractIds))
           || ($i < intval(config('mortgage.alfaMsk.numberIterations')))
        );

        if (count($contracts) !== count($contractIds)) {
            throw new AlphaException('Misstake contractId');
        }

        if ($getResult->getStatusCode() !== 200) {
            throw new AlphaException($message);
        }
        var_dump(json_encode($response));
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function printPolicy(
        Contracts $contract, bool $sample, bool $reset, ?string $filePath = null
    ): string
    {
        $merchantService = new MerchantServices();

        $response = $merchantService->getContractSigned(
            $contract->getOptionsAttribute()['upid'],
            $contract->getOptionsAttribute()['contractList']
        );


        var_dump($response);die;
    }

    /**
     * @param Contracts $contract
     * @return array
     * @throws \Throwable
     */
    public function getStatus(Contracts $contract): array
    {
        if (!empty($contract->getOptionsAttribute()['orderId'])) {
            if ($contract->status !== Contracts::STATUS_CONFIRMED) {
                try {
                    $clientStatusOrder = new MerchantServices();
                    $statusOrder = $clientStatusOrder->getOrderStatus($contract->getOptionsAttribute()['orderId']);

                } catch (\Throwable $throwable) {
                    self::error($throwable->getMessage());
                }

                if ($statusOrder->get('orderStatus') === self::STATUS_CONFIRMED) {
                    $contract->status = Contracts::STATUS_CONFIRMED;
                    $contract->saveOrFail();
                }
            }
        }

        return $this->getTStatus($contract);
    }

    public function payAccept(Contracts $contract): void
    {
        return;
    }
}
