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
    const POLICY_URL = '/msrv/mortgage/partner/calc';
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

        if ($dataCollect->pluck('objects')->has('life')) {
            $life = $dataCollect->pluck('objects')->pluck('life');
            $calculator->setInsurant($life->get('gender'), $life->get('gender'));
            $calculator->setLifeRisk($life->get('professions', []), $life->get('sports', []));
        }
        if ($dataCollect->pluck('objects')->has('property')) {
            $property = $dataCollect->pluck('objects')->pluck('property');
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
        $subject = $dataCollect->pluck('subject');
        $policy->setBank($data['mortgageeBank'], $data['remainingDebt']);
        $policy->setCalcDate($data['activeFrom']);
        $policy->setInsurer($subject->get('city'), $subject->get('street'));
        $policy->setEmail($dataCollect->get('email'));
        $policy->setFullName($dataCollect->get('firstName'), $dataCollect->get('lastName'), $dataCollect->get('middleName'));
        $policy->setPersonDocument($subject->get('docIssueDate'), $subject->get('docNumber'), $subject->get('docSeries'));
        $policy->setPhone($dataCollect->get('phone'));
        $policy->setAddress(implode(',', [
            $dataCollect->get('city'),
            $dataCollect->get('state'),
            $dataCollect->get('street'),
            $dataCollect->get('house'),
            $dataCollect->get('block'),
            $dataCollect->get('apartment')
        ]));

        $policy->setAddressSquare($dataCollect->pluck('objects')->pluck('property')->get('area'));
        $policy->setDateCreditDoc($dataCollect->get('dateCreditDoc')); //?
        $policy->setNumberCreditDoc($dataCollect->get('numberCreditDoc')); //?

        $result = $this->client->post(
            $this->host . self::POLICY_URL, [
                'json' => $policy->getData()
            ]
        );
        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error create policy');
        }
        $decodeResult = json_decode($result->getBody()->getContents(), true);

        return new CreatedPolicy(
            null,
            null,
            Arr::get($decodeResult, 'lifePremium', 0),
            Arr::get($decodeResult, 'propertyPremium', 0),
            null,
            null
        );
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
