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
use App\Models\Contracts;
use App\Models\Program;
use Arr;
use File;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Log;
use Storage;
use Throwable;

/**
 * Class RensinsDriver
 * @package App\Drivers
 */
class RensinsDriver implements DriverInterface, LocalPaymentDriverInterface
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

    protected ?Program $program = null;

    /**
     * RensinsDriver constructor.
     * @param Repository $repository
     * @param string $prefix
     * @throws ReninsException
     */
    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->httpClient = new ReninsClientService($repository, $prefix);
    }

    /**
     * @param array $data
     * @return CalculatedInterface
     * @throws ReninsException
     */
    public function calculate(array $data): CalculatedInterface
    {
        $propRisks = [];
        $lifeRisks = [];
        if ($this->isLive($data)) {
            $result = $this->httpClient->calculate($this->collectCalcData($data, true));
            $objects = Arr::get($result, 'calcPolicyResult.calcResults.0.policy.insuranceObjects.objects');
            $risks = Arr::first(Arr::pluck(Arr::pluck($objects, 'riskInfo'), 'risks'));
            $lifeRisks = Arr::where($risks, function($value, $key) {
                return in_array(Arr::get($value, 'name'), ['Инвалидность', 'Смерть']);
            });
        }
        if ($this->isProperty($data)) {
            $result = $this->httpClient->calculate($this->collectCalcData($data, false));
            $objects = Arr::get($result, 'calcPolicyResult.calcResults.0.policy.insuranceObjects.objects');
            $risks = Arr::first(Arr::pluck(Arr::pluck($objects, 'riskInfo'), 'risks'));
            $propRisks = Arr::where($risks, function($value, $key) {
                return Arr::get($value, 'name') === 'Страхование имущества';
            });
        }

        $propSum = array_sum(Arr::pluck($propRisks, 'insPrem'));
        $lifeSum = array_sum(Arr::pluck($lifeRisks, 'insPrem'));

        return new Calculated($data['contractId'] ?? null, $lifeSum, $propSum);
    }

    /**
     * @param array $data
     * @param bool $Life
     * @return Arrayable
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
                        'insured' => 'true'
                    ]
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
                        'insured' => 'true'
                    ]
                ],
                '_DIOS_2'
            );
        }

        return $collector;
    }

    /**
     * @param array $data
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
     * @param $programCode
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
     * @param array $data
     * @return bool
     */
    protected function isLive(array $data): bool
    {
        $isLife = $this->getProgram($data['programCode'])->is_life;

        if ($isLife && empty($data['objects']['life'])) {
            throw new ReninsException('Не заполнены данные для страхования жизни.');
        }

        return $isLife;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isProperty(array $data): bool
    {
        $isProperty = $this->getProgram($data['programCode'])->is_property;
        if ($isProperty && empty($data['objects']['property'])) {
            throw new ReninsException('Не заполнены данные для страхования имущества.');
        }

        return $isProperty;
    }


    /**
     * @param Contracts $contract
     * @return array
     * @throws Throwable
     */
    public function getStatus(Contracts $contract): array
    {
        if ($contract->status !== Contracts::STATUS_CONFIRMED) {
            try {
                $result = $this->httpClient->getStatus(
                    collect(
                        [
                            'policyID' => $contract->objects()->firstOrFail()->integration_id
                        ]
                    )
                );
            } catch (Throwable $throwable) {
                Log::error($throwable->getMessage());
                $result = null;
            }

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
        $calc = $this->calculate($data);

        if ($calc->getLifePremium()) {
            $createData = $this->collectCreateData($contract, $data, $calc->getPremiumSum(), true);
            $result = $this->httpClient->import($createData);
            $objects = Arr::get($result, 'policy.insuranceObjects.objects');

            $risks = Arr::first(Arr::pluck($objects, 'riskInfo.risks'), null, []);
            $lifeRisks = Arr::where($risks, function($value, $key) {
                return in_array(Arr::get($value, 'name'), ['Инвалидность', 'Смерть']);
            });
            $policyIdLife = Arr::get($result,'policy.ID');
            $policyNumberLife = Arr::get($result,'policy.number');
            $lifeSum = array_sum(Arr::pluck($lifeRisks, 'insPrem'));
            $this->httpClient->issue(collect(['policyID' => $policyIdLife]));
        }
        if ($calc->getPropertyPremium()) {
            $createData = $this->collectCreateData($contract, $data, $calc->getPremiumSum());
            $result = $this->httpClient->import($createData);
            $objects = Arr::get($result, 'policy.insuranceObjects.objects');
            $risks = Arr::first(Arr::pluck($objects, 'riskInfo.risks'), null, []);
            $propRisks = Arr::where($risks, function($value, $key) {
                return Arr::get($value, 'name') === 'Страхование имущества';
            });
            $policyIdProperty = Arr::get($result,'policy.ID');
            $policyNumberProperty = Arr::get($result,'policy.number');
            $propSum = array_sum(Arr::pluck($propRisks, 'insPrem'));
            $this->httpClient->issue(collect(['policyID' => $policyIdProperty]));
        }

        return new CreatedPolicy(
            null,
            isset($policyIdLife) ? $policyIdLife : null,
            isset($policyIdProperty) ? $policyIdProperty : null,
            $lifeSum ?? null,
            $propSum ?? null,
            $policyNumberLife ?? null,
            $policyNumberProperty ?? null,
        );
    }

    /**
     * @param Contracts $contract
     * @param array $data
     * @param float $paySum
     * @param bool $life
     * @return ReninsCreateCollector
     */
    protected function collectCreateData(
        Contracts $contract,
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
                        'insured' => 'true'
                    ]
                ],
                '_DIOS_2'
            );
        }

        return $collector;
    }

    /**
     * @param Contracts $contract
     * @return array
     * @throws ReninsException
     */
    protected function getFilePolice(Contracts $contract): array
    {
        $objects = $contract->objects;
        $files = [];
        foreach ($objects as $object) {
            $filePathObject = self::createFilePath($contract, $object->id);
            if (!$this->isFilePoliceExitst($contract, $filePathObject)) {
                $this->printPolicy($contract, false, true);
            }
            $files[] = public_path($filePathObject);
        }

        return $files;
    }

    /**
     * @inheritDoc
     */
    public function printPolicy(
        Contracts $contract,
        bool $sample,
        bool $reset,
        ?string $filePath = null
    ) {
        if ($contract->status !== Contracts::STATUS_CONFIRMED) {
            throw new ReninsException('Status is not confirmed!');
        }
        $filesOut = [];
        $objects = $contract->objects;
        foreach ($objects as $object) {
            $filePathObject = self::createFilePath($contract, $object->id);
            if ($this->isFilePoliceExitst($contract, $filePathObject)) {
                $filesOut[] = self::generateBase64($filePathObject);
                continue;
            }
            $url = $this->httpClient->print(
                collect(
                    [
                        'calcID' => $object->integration_id,
                        'type' => 'Печать'
                    ]
                )
            );
            throw_if(!$url, ReninsException::class, ['message' => 'Url not get!']);
            $path = $this->httpClient->getFile($url);

            $dirFiles = self::unpackZip($path);
            $files = Storage::allFiles($dirFiles);
            $file = collect($files)->first(function($file) {
                return stripos(last(explode(DIRECTORY_SEPARATOR, $file)), 'polis') !== false;
            });
            throw_if(!$file, new ReninsException('Police file not set.'));
            $actualFilePath = self::createFilePath($contract, $object->id);
            File::move(storage_path('app/' . $file), public_path($actualFilePath));

            $filesOut[] = self::generateBase64(public_path($actualFilePath));
        }

        return $filesOut;
    }

    /**
     * @param Contracts $contract
     * @param $objectId
     * @return string
     */
    protected static function createFilePath(Contracts $contract, $objectId): string
    {
        $filePathObject = self::gefaultFileName($contract);
        $filePathObjectArray = explode('.', $filePathObject);
        $ext = array_pop($filePathObjectArray);
        array_push($filePathObjectArray, $objectId, $ext);
        $filePathObject = implode('.', $filePathObjectArray);

        return $filePathObject;
    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contracts $contract): void
    {
        return;
    }
}
