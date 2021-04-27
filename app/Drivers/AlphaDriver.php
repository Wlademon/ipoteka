<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Source\Alpha\AlphaCalculator;
use App\Drivers\Traits\DriverTrait;
use App\Exceptions\Drivers\AlphaException;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;
use GuzzleHttp\Client;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;

/**
 * Class AbsolutDriver
 * @package App\Drivers
 */
class AlphaDriver implements DriverInterface
{
    use DriverTrait;

    const CALC_URL = '/msrv/mortgage/partner/calc';
    const POST_POLICY_URL = '/msrv/mortgage/partner/calc';
    const GET_POLICY_URL = '/msrv/mortgage/partner/contractStatus?upid=360';
    protected string $host;
    protected Client $client;

    public function __construct(Client $client, Repository $repository, string $prefix = '')
    {
        $this->client = $client;
        throw_if(
            !$repository->get($prefix . '.host', false), new AlphaException('Not set host property')
        );
        $this->host = $repository->get($prefix . '.host');
    }

    /**
     * @inheritDoc
     */
    public function calculate(array $data): CalculatedInterface
    {

        $calculator = $this->collectData($data);
        $result = $this->client->post(
            $this->host . self::CALC_URL, [
                'json' => $calculator->getData()
            ]
        );
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
        $calculator = new AlphaCalculator(config(), '');
        $calculator->setBank($data['mortgageeBank'], $data['remainingDebt']);
        $calculator->setCalcDate($data['activeFrom']);

        if ($dataCollect->flatten()->has('life')) {
            $life = $dataCollect->flatten(2);
            $calculator->setInsurant($life->get('gender'), $life->get('gender'));
            $calculator->setLifeRisk($life->get('professions', []), $life->get('sports', []));
        }
        if ($dataCollect->flatten()->has('property')) {
            $property = $dataCollect->flatten(2);
            $calculator->setInsurance();
            $calculator->setPropertyRisk(
                null,
                $property->get('isWooden', false),
                $property->get('buildYear')
            );
        }

        return $calculator;
    }

    /**
     * @inheritDoc
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {

    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {

        $dataCollect = collect($data);
        $policy = $this->CollectData($data);
        $subject = $dataCollect->flatten();
        $policy->setBank($data['mortgageeBank'], $data['remainingDebt']);
        $policy->setCalcDate($data['activeFrom']);
        $policy->setInsurer($subject->get('city'), $subject->get('street'));
        $policy->setEmail($dataCollect->get('email'));
        $policy->setFullName($dataCollect->get('firstName'), $dataCollect->get('lastName'), $dataCollect->get('middleName'));
        $policy->setPersonDocument($subject->get('docIssueDate'), $subject->get('docNumber'), $subject->get('docSeries'));
        $policy->setPhone($dataCollect->get('phone'));
        $policy->setAddress($dataCollect->only(['city', 'state', 'street', 'house', 'block', 'apartment'])->join(', '));

        $policy->setAddressSquare($dataCollect->flatten(2)->get('area'));
        $policy->setDateCreditDoc($dataCollect->get('dateCreditDoc')); //?
        $policy->setNumberCreditDoc($dataCollect->get('numberCreditDoc')); //?

        $postResult = $this->client->post(
            $this->host . self::POST_POLICY_URL, [
                'json' => $policy->getData()
            ]
        );
        if ($postResult->getStatusCode() !== 200) {
            throw new AlphaException('Error create policy');
        }
        $decodePostResult = json_decode($postResult->getBody()->getContents(), true);

        $getResult = $this->client->get(
            $this->host . self::GET_POLICY_URL, [
                'upid' => Arr::get($decodePostResult, 'upid'),
            ]
        );
        if ($getResult->getStatusCode() !== 200) {
            throw new AlphaException('Error get data from createPolicy');
        }
        $decodeGetResult = json_decode($getResult->getBody()->getContents(), true);

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

    public function sendRequest($host)
    {

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
