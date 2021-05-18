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

/**
 * Class AbsolutDriver
 * @package App\Drivers
 */
class AlfaMskDriver implements DriverInterface
{
    use DriverTrait;

    const POST_POLICY_URL = '/msrv/mortgage/partner/calc';
    const GET_POLICY_URL = '/msrv/mortgage/partner/contractStatus';
    const POST_PAYMENT_RECEIPT = '/msrvg/payment/receipt';

    protected string $host;
    protected Client $client;
    protected int $managerId = 0;
    protected array $contractList = [];

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
        }
        catch (\Throwable $e) {
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
    protected function createSingleAccount(): array
    {
        $result = $this->client->post(
            $this->host . self::POST_PAYMENT_RECEIPT, [
                'json' => [
                    'contractList' => implode(',', $this->contractList)
                ]
            ]
        );
        throw_if($result->getStatusCode() !== 200, new AlphaException('Error create payment'));
        $decodeResult = json_decode($result->getBody()->getContents(), true);
        $text = ' Оплата страхового полиса ';
        return [
            $text . Arr::get($decodeResult, 'id', 0),
            $text . Arr::get($decodeResult, 'number', 0),
        ];
    }

//    protected function getCross($contract)
//    {
//        if (empty($contract->id)) {
//            throw new AlphaException('contractId is empty.');
//        }
//
//        $getResult = $this->client->get(
//            $this->host . self::CROSS_URL, [
//                'contractId' => $contract->id,
//            ]
//        );
//        if ($getResult->getStatusCode() !== 200) {
//            throw new AlphaException('Error get data from getCross');
//        }
//        $decodeGetResult = json_decode($getResult->getBody()->getContents(), true);
//
//        $collectResult = collect($decodeGetResult);
//        if (!$collectResult->has('crossProductList')) {
//            return $decodeGetResult;
//        }
//
//        $calculationIdList = [];
//        foreach ($collectResult->only('crossProductList') as $param) {
//            $calculationIdList = $param['id'];
//        }
//
//        return [
//            $decodeGetResult,
//            $calculationIdList,
//            $contract->id . $this->managerId
//        ];
//    }
//
//    protected function postSaveCross($contract): string
//    {
//        $postResult = $this->client->post(
//            $this->host . self::CROSS_URL, [
//                'json' => $this->getCross($contract)
//            ]
//        );
//        if ($postResult->getStatusCode() !== 200) {
//            throw new AlphaException('Error save crosses in contract.');
//        }
//
//        $decodeGetResult = json_decode($postResult->getBody()->getContents(), true);
//
//        return Arr::get($decodeGetResult, 'saveRequestId', 0);
//    }

//    protected function getCrossStatus(Contracts $contract)
//    {
//        $getResult = $this->client->get(
//            $this->host . self::CROSS_URL, [
//                'contractId' => $this->postSaveCross($contract),
//            ]
//        );
//        if ($getResult->getStatusCode() !== 200) {
//            throw new AlphaException('Error get data from CrossStatus');
//        }
//        $decodeGetResult = json_decode($getResult->getBody()->getContents(), true);
//    }

    protected function getIsOperDocument(): string // заглушка
    {
        return 'y';
    }

    /**
     * @throws AlphaException
     */
    public function registerUserProfile($contract): string
    {
        $registerProfile = new RegisterUserProfile();
        $registerProfile->setEmail($contract->subject()->value['email']);
        $registerProfile->setFullName(
            $contract->subject()->value['firstName'],
            $contract->subject()->value['lastName'],
            $contract->subject()->value['middleName']
        );
        $registerProfile->setPassword(null);
        $registerProfile->setPhone($contract->subject()->value['phone']); //Телефон. В любом формате, главное чтобы были все 11 цифр
        $registerProfile->setBirthday((new Carbon($contract->subject()->value['birthDate']))->format('d.m.Y')); //birthday=08.11.1985
        $registerProfile->setContractNumber($contract->number);
        $registerProfile->setOfferAccepted('y'); // заглушка без метода
        $registerProfile->setPartnerName('E_PARTNER'); //заглушка без метода

        return $registerProfile->registerProfile($this->client, env('MS_REGISTER_USER_PROFILE'));
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {
        $registerOrder = new MerchantServices();
        $registerOrder->setMerchantOrderNumber($contract->id);
        $registerOrder->setDescription($this->createSingleAccount());
        $registerOrder->setExpirationDate(Carbon::now()->addMinutes(20)->format('Y-m-d\TH:i:sP'));
        $registerOrder->setIsOperDocument($this->getIsOperDocument());
        $registerOrder->setClientId($this->registerUserProfile($contract));
        $registerOrder->setReturnUrl($payLinks->getSuccessUrl());
        $registerOrder->setFailUrl($payLinks->getFailUrl());

        $response = $registerOrder->registerOrder();

        return new PayLink(
            $response->orderId->return,
            $response->formUrl->return,
            $contract->insured_sum
        );
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {
        dd(123);
        $dataCollect = collect($data);
        $policy = $this->CollectData($data);
        $subject = $dataCollect->only('subject')->flatten();
        $property = $dataCollect->only('objects')->flatten()->only(['property'])->flatten();

        $policy->setInsurer($subject->get('city'), $subject->get('street'));
        $policy->setEmail($subject->get('email'));
        $policy->setFullName($subject->get('firstName'), $subject->get('lastName'), $subject->get('middleName'));
        $policy->setPersonDocument($subject->get('docIssueDate'), $subject->get('docNumber'), $subject->get('docSeries'));
        $policy->setPhone($subject->get('phone'));

        if ($property->isNotEmpty()) {
            $policy->setAddress($property->only(['city', 'state', 'street', 'house', 'block', 'apartment'])->join(','));
            $policy->setAddressSquare($property->get('area'));
        }

        $policy->setDateCreditDoc((new Carbon())->format('Y-d-m')); //?
        $policy->setNumberCreditDoc($dataCollect->get('mortgageAgreementNumber')); //?

        $postResult = $this->client->post(
            $this->host . self::POST_POLICY_URL, [
                'json' => $policy->getData()
            ]
        );
        if ($postResult->getStatusCode() !== 200) {
            throw new AlphaException('Error create policy');
        }
        $decodePostResult = json_decode($postResult->getBody()->getContents(), true);


        if (!$decodePostResult->has('upid')) {
            throw new AlphaException('Response has not upid');
        }

        $decodeGetResult = $this->getStatusContract(Arr::get($decodePostResult, 'upid'), 'Error get data from createPolicy');

        if (Arr::has($decodeGetResult['propertyContract'], 'contractId')) {
            $this->contractList[] = Arr::get($decodeGetResult['propertyContract'], 'contractId', 0);
        }
        if (Arr::has($decodeGetResult['lifeContract'], 'contractId')) {
            $this->contractList[] = Arr::get($decodeGetResult['lifeContract'], 'contractId', 0);
        }

        return new CreatedPolicy(
            $contract->id,
            Arr::get($decodeGetResult, 'lifePremium', 0),
            Arr::get($decodeGetResult, 'propertyPremium', 0),
            Arr::get($decodeGetResult['lifeContract'], 'contractNumber', 0),
            Arr::get($decodeGetResult['propertyContract'], 'contractNumber', 0),
            Arr::get($decodeGetResult['lifeContract'], 'contractId', 0),
            Arr::get($decodeGetResult['propertyContract'], 'contractId', 0),
        );
    }

    protected function getStatusContract($upid, $message)
    {
        $getResult = $this->client->get(
            $this->host . self::GET_POLICY_URL, [
                'upid' => $upid,
            ]
        );
        if ($getResult->getStatusCode() !== 200) {
            throw new AlphaException($message);
        }

        return json_decode($getResult->getBody()->getContents(), true);
    }

    /**
     * @inheritDoc
     */
    public function printPolicy(
        Contracts $contract, bool $sample, bool $reset, ?string $filePath = null
    ): string
    {

    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contracts $contract): void
    {

    }
}
