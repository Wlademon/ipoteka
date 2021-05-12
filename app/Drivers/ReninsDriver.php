<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLink;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Source\Renins\ReninsCalcCollector;
use App\Drivers\Source\Renins\ReninsClientService;
use App\Drivers\Source\Renins\ReninsCreateCollector;
use App\Drivers\Source\Renins\TokenService;
use App\Drivers\Traits\DriverTrait;
use App\Drivers\Traits\PrintPdfTrait;
use App\Drivers\Traits\ZipTrait;
use App\Exceptions\Drivers\ReninsException;
use App\Models\Contracts;
use App\Models\Programs;
use App\Services\HttpClientService;
use App\Services\PayService\PayLinks;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

/**
 * Class ReninsDriver
 * @package App\Drivers
 */
class ReninsDriver implements DriverInterface
{
    use DriverTrait {
        DriverTrait::getStatus as getTStatus;
    }
    use PrintPdfTrait;
    use ZipTrait;

    const CREDIT_CITY = 'Москва';

    const ISSUE_PREPARATION = 'ISSUE_PREPARATION';
    const PAYMENT_PREPARATION = 'PAYMENT_PREPARATION';
    const PAYMENT_WAITING = 'PAYMENT_WAITING';
    const ISSUE_SUCCESSFUL = 'ISSUE_SUCCESSFUL';

    /** @var ReninsClientService */
    protected ReninsClientService $httpClient;

    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->httpClient = new ReninsClientService($repository, $prefix);
    }

    /**
     * @inheritDoc
     *
     */
    public function calculate(array $data): CalculatedInterface
    {
        $result = $this->httpClient->calculate($this->collectCalcData($data));
        $objects = \Arr::get($result, 'calcPolicyResult.calcResults.insuranceObjects.objects');
        $risks = \Arr::pluck($objects, 'riskInfo.risks');
        $propRisks = \Arr::where($risks, function($value, $key) {
            return \Arr::get($value, 'name') === 'Страхование имущества';
        });
        $lifeRisks = \Arr::where($risks, function($value, $key) {
            return in_array(\Arr::get($value, 'name'), ['Инвалидность', 'Страхование имущества']);
        });

        $propSum = array_sum(\Arr::pluck($propRisks, 'insPrem'));
        $lifeSum = array_sum(\Arr::pluck($lifeRisks, 'insPrem'));

        return new Calculated(null, $lifeSum, $propSum);
    }

    protected function collectCalcData(array $data): Arrayable
    {
        $collector = new ReninsCalcCollector();
        $collector->setBankBik($this->getBankBIKByParams($data));
        $collector->setCreditSum($data['remainingDebt']);
        $collector->setCreditCity(self::CREDIT_CITY);
        $collector->workStatus($data['subject']);
        if ($this->isLive($data)) {
            $objectLife = $data['objects']['life'];
            $collector->setSex($objectLife['gender']);
            $collector->subjectIsObject();
            $collector->setBirthDate($objectLife['birthDate']);
            $collector->addObject(
                [
                    [
                        'name' => 'Смерть',
                        'insured' => 'true'
                    ],
                    [
                        'name' => 'Инвалидность',
                        'insured' => 'true'
                    ],
                ],
                '_zastr1'
            );
        }
        if ($this->isProperty($data)) {
            $objectProperty = $data['objects']['property'];
            $collector->setBuildDate($objectProperty['buildYear']);
            $collector->addObject(
                [
                    [
                        'name' => 'Страхование имущества',
                        'insured' => 'true'
                    ]
                ],
                '_DIOS_2'
            );
        }

        return $collector;
    }

    public function getBankBIKByParams(array $data): ?string
    {
        $banks = $this->getProgram($data['programCode'])->conditions['mortgageeBanks'];
        foreach ($banks as ['bankName' => $bankName, 'bankAccount' => $bankAccount]) {
            if ($data['mortgageeBank'] === $bankName) {
                return $bankAccount;
            }
        }

        return null;
    }

    public function getProgram($programCode): Programs
    {
        return Programs::whereProgramCode($programCode)->firstOrFail();
    }

    protected function isLive(array $data)
    {
        return !empty($data['objects']['life']);
    }

    protected function isProperty(array $data)
    {
        return !empty($data['objects']['property']);
    }

    /**
     * @inheritDoc
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {
        $linkData = $this->httpClient->payLink(
            collect(
                [
                    'policyID' => $contract->objects()->firstOrFail()->external_id
                ]
            )
        );
        parse_str(parse_url($linkData['url'], PHP_URL_QUERY), $result);

        return new PayLink($result['mdOrder'], $linkData['url'], $contract->insured_sum);
    }

    public function getStatus(Contracts $contract): array
    {
        if ($contract->status !== Contracts::STATUS_CONFIRMED) {
            $result = $this->httpClient->getStatus(
                collect(
                    [
                        'policyID' => $contract->objects()->firstOrFail()->external_id
                    ]
                )
            );

            if ($result === self::ISSUE_SUCCESSFUL) {
                $contract->status = Contracts::STATUS_CONFIRMED;
                $contract->saveOrFail();
            }
        }

        return $this->getTStatus($contract);
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {
        $createData = $this->collectCreateData($contract, $data, $this->calculate($data)->getPremiumSum());
        $result = $this->httpClient->import($createData);

        $objects = \Arr::get($result, 'policy.insuranceObjects.objects');
        $risks = \Arr::pluck($objects, 'riskInfo.risks');
        $propRisks = \Arr::where($risks, function($value, $key) {
            return \Arr::get($value, 'name') === 'Страхование имущества';
        });
        $lifeRisks = \Arr::where($risks, function($value, $key) {
            return in_array(\Arr::get($value, 'name'), ['Инвалидность', 'Страхование имущества']);
        });
        $policyId = \Arr::get($result,'policy.ID');
        $policyNumber = \Arr::get($result,'policy.number');
        $propSum = array_sum(\Arr::pluck($propRisks, 'insPrem'));
        $lifeSum = array_sum(\Arr::pluck($lifeRisks, 'insPrem'));
        $this->httpClient->issueAsync(collect(['policyID' => $policyId]));
        return new CreatedPolicy(
            null,
            $lifeSum ? $policyId : null,
            $propSum ? $policyId : null,
            $lifeSum,
            $propSum,
            $lifeSum ? $policyNumber : null,
            $propSum ? $policyNumber : null,
        );
    }

    protected function collectCreateData(Contracts $contract, array $data, float $paySum)
    {
        $isLive = $this->isLive($data);
        $isProp = $this->isProperty($data);
        $collector = new ReninsCreateCollector();
        $collector->setPayPlan($contract->active_from, $paySum);
        $collector->setStartEnd($contract->active_from, $contract->active_to);
        $collector->setCreditSum($data['remainingDebt']);
        $collector->setHumanInfo($data['subject']);
        $collector->setCreditNumber($data['mortgageAgreementNumber']);
        $collector->setCreditCity(self::CREDIT_CITY);
        $collector->workStatus($data['subject']);
        if ($isLive) {
            $collector->addObject(
                [
                    [
                        'name' => 'Смерть',
                        'insured' => 'true'
                    ],
                    [
                        'name' => 'Инвалидность',
                        'insured' => 'true'
                    ],
                ],
                '_zastr1'
            );
        }
        if ($isProp) {
            $objectProperty = $data['objects']['property'];
            $collector->setBuildDate($objectProperty['buildYear']);
            $collector->addObject(
                [
                    [
                        'name' => 'Страхование имущества',
                        'insured' => 'true'
                    ]
                ],
                '_DIOS_2'
            );
        }

        return $collector;
    }

    /**
     * @inheritDoc
     */
    public function printPolicy(
        Contracts $contract,
        bool $sample,
        bool $reset,
        ?string
        $filePath = null
    ): string {
        if ($contract->status !== Contracts::STATUS_CONFIRMED) {
            throw new ReninsException('Status is not confirmed!');
        }
        if ($this->isFilePoliceExitst($contract, $filePath)) {
            return self::generateBase64($filePath);
        }
        $url = $this->httpClient->print(
            collect(
                [
                    'policyID' => $contract->objects()->firstOrFail()->external_id
                ]
            )
        );
        throw_if(!$url, ReninsException::class, ['message' => 'Url not get!']);
        $path = $this->httpClient->getFile($url);
        $dirFiles = self::unpackZip($path);
        $files = \Storage::allFiles($dirFiles);
        $file = collect($files)->first(function($file) {
            return stripos(last(explode(DIRECTORY_SEPARATOR, $file)), 'polis') !== false;
        });
        throw_if(!$file, ReninsException::class, ['Police file not set.']);
        $actualFilePath = $this->gefaultFileName($contract);
        \Storage::copy($file, 'public/' . $actualFilePath);

        return self::generateBase64(public_path($actualFilePath));
    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contracts $contract): void
    {
        return;
    }
}
