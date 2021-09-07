<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\Source\Renins\ReninsCalcCollector;
use App\Drivers\Source\Renins\ReninsClientService;
use App\Drivers\Source\Renins\ReninsCreateCollector;
use App\Drivers\Traits\DriverTrait;
use App\Drivers\Traits\PrintPdfTrait;
use App\Drivers\Traits\ZipTrait;
use App\Exceptions\Drivers\ReninsException;
use App\Models\Contract;
use App\Models\Program;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Class RensinsDriver
 *
 * @package App\Drivers
 */
class RensinsDriver implements DriverInterface, LocalPaymentDriverInterface, OutPrintDriverInterface
{
    use DriverTrait {
        DriverTrait::getStatus as getTStatus;
    }
    use PrintPdfTrait;
    use ZipTrait;

    public const CREDIT_CITY = 'Москва';
    public const ISSUE_SUCCESSFUL = 'ISSUE_SUCCESSFUL';
    /** @var ReninsClientService */
    protected ReninsClientService $httpClient;
    protected ?Program $program = null;

    /**
     * RensinsDriver constructor.
     *
     * @param  ReninsClientService  $service
     */
    public function __construct(ReninsClientService $service)
    {
        $this->httpClient = $service;
    }

    /**
     * @param  array  $data
     *
     * @return CalculatedInterface
     * @throws ReninsException
     */
    public function calculate(array $data): CalculatedInterface
    {
        $propRisks = [];
        $lifeRisks = [];
        if ($this->isLive($data)) {
            $result = $this->httpClient->calculate($this->collectCalcData($data, true));
            $objects = Arr::get(
                $result,
                'calcPolicyResult.calcResults.0.policy.insuranceObjects.objects'
            );
            $risks = Arr::first(Arr::pluck(Arr::pluck($objects, 'riskInfo'), 'risks'));
            $lifeRisks = Arr::where(
                $risks,
                function ($value, $key)
                {
                    return in_array(Arr::get($value, 'name'), ['Инвалидность', 'Смерть']);
                }
            );
        }
        if ($this->isProperty($data)) {
            $result = $this->httpClient->calculate($this->collectCalcData($data, false));
            $objects = Arr::get(
                $result,
                'calcPolicyResult.calcResults.0.policy.insuranceObjects.objects'
            );
            $risks = Arr::first(Arr::pluck(Arr::pluck($objects, 'riskInfo'), 'risks'));
            $propRisks = Arr::where(
                $risks,
                function ($value, $key)
                {
                    return Arr::get($value, 'name') === 'Страхование имущества';
                }
            );
        }

        $propSum = array_sum(Arr::pluck($propRisks, 'insPrem'));
        $lifeSum = array_sum(Arr::pluck($lifeRisks, 'insPrem'));

        return new Calculated($data['contractId'] ?? null, $lifeSum, $propSum);
    }

    /**
     * @param  array  $data
     * @param  bool   $Life
     *
     * @return Arrayable
     * @throws ReninsException
     */
    protected function collectCalcData(array $data, bool $Life = false): Arrayable
    {
        $collector = new ReninsCalcCollector();
        $collector->setBankBik($this->getBankBIKByParams($data));
        $collector->setCreditSum($data['remainingDebt']);
        $collector->setCreditCity(self::CREDIT_CITY);
        $collector->setContractStartEnd($data['activeFrom'], $data['activeTo']);
        if ($Life && $this->isLive($data)) {
            $objectLife = $data['objects']['life'];
            $collector->setSex($objectLife['gender']);
            $collector->subjectIsObject();
            $collector->workStatus($objectLife);
            $collector->setBirthDate($objectLife['birthDate']);
            $collector->addObject(
                [
                    [
                        'name' => 'Смерть',
                        'insured' => 'true',
                    ],
                ],
                '_zastr1'
            );
        }
        if (!$Life && $this->isProperty($data)) {
            $collector->workStatus(['professions' => ['Рабочий']]);
            $collector->setBirthDate(date('Y-m-d', strtotime('-25 years')));
            $objectProperty = $data['objects']['property'];
            $collector->setBuildDate($objectProperty['buildYear']);
            $collector->addObject(
                [
                    [
                        'name' => 'Страхование имущества',
                        'insured' => 'true',
                    ],
                ],
                '_DIOS_2'
            );
        }

        return $collector;
    }

    /**
     * @param  array  $data
     *
     * @return string|null
     */
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

    /**
     * @param  string  $programCode
     *
     * @return Program
     */
    public function getProgram(string $programCode): Program
    {
        if (!$this->program) {
            $this->program = Program::where('program_code', $programCode)->firstOrFail();
        }

        return $this->program;
    }

    /**
     * @param  array  $data
     *
     * @return bool
     * @throws ReninsException
     */
    protected function isLive(array $data): bool
    {
        $isLife = $this->getProgram($data['programCode'])->is_life;

        if ($isLife && empty($data['objects']['life'])) {
            throw new ReninsException(__METHOD__, 'Не заполнены данные для страхования жизни.');
        }

        return $isLife;
    }

    /**
     * @param  array  $data
     *
     * @return bool
     * @throws ReninsException
     */
    protected function isProperty(array $data): bool
    {
        $isProperty = $this->getProgram($data['programCode'])->is_property;
        if ($isProperty && empty($data['objects']['property'])) {
            throw new ReninsException(__METHOD__, 'Не заполнены данные для страхования имущества.');
        }

        return $isProperty;
    }

    /**
     * @param  Contract  $contract
     *
     * @return array
     * @throws Throwable
     */
    public function getStatus(Contract $contract): array
    {
        if ($contract->status !== Contract::STATUS_CONFIRMED) {
            try {
                $result = $this->httpClient->getStatus(
                    collect(
                        [
                            'policyID' => $contract->objects()->firstOrFail()->integration_id,
                        ]
                    )
                );
            } catch (Throwable $throwable) {
                Log::error($throwable->getMessage());
                $result = null;
            }

            if ($result === self::ISSUE_SUCCESSFUL) {
                $contract->status = Contract::STATUS_CONFIRMED;
                $contract->saveOrFail();
            }
        }

        return $this->getTStatus($contract);
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface
    {
        $calc = $this->calculate($data);

        if ($calc->getLifePremium()) {
            $createData = $this->collectCreateData($contract, $data, $calc->getPremiumSum(), true);
            $result = $this->httpClient->import($createData);
            $objects = Arr::get($result, 'policy.insuranceObjects.objects');

            $risks = Arr::first(Arr::pluck($objects, 'riskInfo.risks'), null, []);
            $lifeRisks = Arr::where(
                $risks,
                function ($value, $key)
                {
                    return in_array(Arr::get($value, 'name'), ['Инвалидность', 'Смерть']);
                }
            );
            $policyIdLife = Arr::get($result, 'policy.ID');
            $policyNumberLife = Arr::get($result, 'policy.number');
            $lifeSum = array_sum(Arr::pluck($lifeRisks, 'insPrem'));
            $this->httpClient->issue(collect(['policyID' => $policyIdLife]));
        }
        if ($calc->getPropertyPremium()) {
            $createData = $this->collectCreateData($contract, $data, $calc->getPremiumSum());
            $result = $this->httpClient->import($createData);
            $objects = Arr::get($result, 'policy.insuranceObjects.objects');
            $risks = Arr::first(Arr::pluck($objects, 'riskInfo.risks'), null, []);
            $propRisks = Arr::where(
                $risks,
                function ($value, $key)
                {
                    return Arr::get($value, 'name') === 'Страхование имущества';
                }
            );
            $policyIdProperty = Arr::get($result, 'policy.ID');
            $policyNumberProperty = Arr::get($result, 'policy.number');
            $propSum = array_sum(Arr::pluck($propRisks, 'insPrem'));
            $this->httpClient->issue(collect(['policyID' => $policyIdProperty]));
        }

        return new CreatedPolicy(
            null,
            $policyIdLife ?? null,
            $policyIdProperty ?? null,
            $lifeSum ?? null,
            $propSum ?? null,
            $policyNumberLife ?? null,
            $policyNumberProperty ?? null,
        );
    }

    /**
     * @param  Contract  $contract
     * @param  array     $data
     * @param  float     $paySum
     * @param  bool      $life
     *
     * @return ReninsCreateCollector
     */
    protected function collectCreateData(
        Contract $contract,
        array $data,
        float $paySum,
        bool $life = false
    ): ReninsCreateCollector {
        $collector = new ReninsCreateCollector();
        $collector->setPayPlan($contract->active_from, $paySum);
        $collector->setContractStartEnd($contract->active_from, $contract->active_to);
        $collector->setCreditSum($data['remainingDebt']);
        $collector->setHumanInfo($data['subject']);
        $collector->setCreditNumber($data['mortgageAgreementNumber']);
        $collector->setBankBik($this->getBankBIKByParams($data));
        $collector->setCreditCity(self::CREDIT_CITY);
        $collector->workStatus($data['subject']);
        $collector->setBirthDateSubject($data['subject']['birthDate']);
        if ($life) {
            $collector->setBirthDate($data['objects']['life']['birthDate']);
            $collector->addObject(
                [
                    [
                        'name' => 'Смерть',
                        'insured' => 'true',
                    ],
                    [
                        'name' => 'Инвалидность',
                        'insured' => 'true',
                    ],
                ],
                '_zastr1'
            );
        }
        if (!$life) {
            $objectProperty = $data['objects']['property'];
            $state = Arr::get($objectProperty, 'state');
            $city = Arr::get($objectProperty, 'city');
            $street = Arr::get($objectProperty, 'street');
            $house = Arr::get($objectProperty, 'house');
            $collector->setPropertyAddress($state, $city, $street, $house);
            $collector->setBuildDate($objectProperty['buildYear']);
            $collector->addObject(
                [
                    [
                        'name' => 'Страхование имущества',
                        'insured' => 'true',
                    ],
                ],
                '_DIOS_2'
            );
        }

        return $collector;
    }

    /**
     * @inheritDoc
     * @throws ReninsException|Throwable
     */
    public function printPolicy(
        Contract $contract,
        bool $sample,
        bool $reset
    ): array {
        if ($contract->status !== Contract::STATUS_CONFIRMED) {
            throw new ReninsException(__METHOD__, 'Status is not confirmed!');
        }
        $filesOut = [];
        $objects = $contract->objects;
        foreach ($objects as $object) {
            $url = $this->httpClient->print(
                collect(
                    [
                        'calcID' => $object->integration_id,
                        'type' => 'Печать',
                    ]
                )
            );
            throw_if(!$url, new ReninsException(__METHOD__, 'Url not get!'));
            $path = $this->httpClient->getFile($url);

            $dirFiles = self::unpackZip($path);
            $files = Storage::allFiles($dirFiles);
            $file = collect($files)->first(
                function ($file)
                {
                    return stripos(last(explode(DIRECTORY_SEPARATOR, $file)), 'polis') !== false;
                }
            );
            throw_if(!$file, new ReninsException(__METHOD__, 'Файл полиса не установлен.'));

            $filesOut[$object->id] = self::generateBase64(storage_path('app/' . $file));
        }

        return $filesOut;
    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contract $contract): void
    {
    }

    /**
     * @return string
     */
    public static function code(): string
    {
        return 'rensins';
    }

    /**
     * @param  Contract  $contract
     *
     * @return array
     */
    public function getPoliceIds(Contract $contract): array
    {
        return $contract->objects->pluck('id')->all();
    }
}
