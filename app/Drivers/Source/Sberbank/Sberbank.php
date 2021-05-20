<?php


namespace App\Drivers\Source\Sberbank;


use App\Drivers\DriverInterface;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Traits\LoggerTrait;
use App\Drivers\Traits\PlugDriverTrait;
use App\Exceptions\Drivers\BaseDriverException;
use App\Helpers\Helper;
use App\Models\Companies;
use App\Models\Contracts;
use App\Models\Objects;
use App\Models\Owners;
use App\Models\Programs;
use App\Models\Subjects;
use App\Services\PayService\PayLinks;
use App\Services\Service;
use App\Services\SiteService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HigherOrderCollectionProxy;

class Sberbank
{

    use LoggerTrait;

    protected ?Request $request;
    /**
     * Данные полученные из request
     * @see Sberbank::createPolicy()
     * @var array|null
     */
    protected ?array $data;
    protected ?Programs $program;
    protected ?Carbon $activeFrom;
    protected ?Carbon $activeTo;
    protected ?Carbon $signedAt;
    protected ?Companies $company;
    protected ?Contracts $contract;

    /**
     * @var string|null Источник
     * @see Sberbank::collectData()
     */
    protected ?string $trafficSource;

    /**
     * @var array|null Посчитанные данные
     * @see Sberbank::collectData()
     */
    protected ?array $calcData;



    public function getCalculate($data)
    {
        return [
            "success" => true,
            "data" => [
                "contractId" => 10,
                "propertyPremium" => 1000.40
            ]
        ];
    }

    /**
     * Создание полиса
     * @param Request $request
     * @param Request $request
     * @return array
     */
    public function createPolicy(Contracts $contract, array $data)
    {
        $this->data = $data;
        $this->isSaved = false;

        $this->collectData();

        if ($this->validate()) {
                $this->isSaved = $this->saveOrUpdate();
//                $this->afterSaveOrUpdate();
        }
        dd($this->getResultData());
        return $this->getResultData();
    }

    /**
     * Сбор общих данных для дальнейших операций
     */
    protected function collectData(): void
    {
        static::log('Start calculate');
        $this->activeFrom = Carbon::parse($this->data[0]['activeFrom']);
        $this->activeTo = Carbon::parse($this->data[0]['activeTo']);
        $this->signedAt = Carbon::now()->startOfDay();


        $this->calcData = $this->getCalculate($this->data);


        $this->program = Programs::with('company')->findOrFail($this->calcData['data']['contractId']);

        $this->company = $this->program->company;

        $ownerCode = Arr::get($this->data, 'ownerCode', 'STRAHOVKA');

        $this->owner = Owners::where('code', $ownerCode)->first();
        $siteService = new SiteService();
        $user = $siteService->getUserData($this->data[0]['subject']);

        if ($user) {
            $user['login'] = Arr::get($user, 'login');
            $user['subjectId'] = Arr::get($user, 'subjectId');
        }
    }

    /**
     * Поиск ошибок при создании полиса
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        /** @var array|HigherOrderCollectionProxy|mixed $conditions */

        $conditions = $this->program->conditions;
        $validActiveFromMin = Carbon::now()->startOfDay()->addDays($conditions->timeFranchise);
        if ($this->activeFrom < $validActiveFromMin) {
            self::abortLog(
                "Дата начала полиса не может быть раньше чем дата заключения (сегодня) + временная франшиза {$conditions->timeFranchise} дней",
                BaseDriverException::class
            );

            return false;
        }
        if (
            ($conditions->insuredPolicyHolder ?? false) &&
            $conditions->maxInsuredCount == 1 &&
            count($this->data['objects'])
        ) {
            $object = collect(current($this->data['objects']));
            $subject = collect($this->data['subject']);
            if (
            !(
                $object->has('firstName') &&
                $subject->has('firstName') &&
                $object->has('lastName') &&
                $subject->has('lastName') &&
                $object->has('birthDate') &&
                $subject->has('birthDate') &&
                $object->get('firstName', false) === $subject->get('firstName') &&
                $object->get('lastName', false) === $subject->get('lastName') &&
                $object->get('birthDate', false) === $subject->get('birthDate')
            )
            ) {
                self::log(json_encode([$this->data['objects'], $this->data['subject']]));
                self::abortLog(
                    'По условиям программы, страхователь и страхуемы должны быть одним человеком!',
                    BaseDriverException::class
                );
            }
        }

        return true;
    }

    /**
     * Сохранить данные полиса
     * @return bool
     * @throws \Exception
     */
    protected function saveOrUpdate(): bool
    {
//        if (!empty($this->data['id'])) {
//            return $this->update();
//        }

        return $this->save();
    }

    protected function upsert(Contracts $contract): bool
    {
        $options = Arr::except($this->data[0],['object', 'subject']);

        $contract->options = json_encode($options, JSON_UNESCAPED_UNICODE);
        $contract->active_from = Carbon::parse(Arr::get($this->data,'activeFrom'))->startOfDay()->format('Y-m-d H:i:s');
        $contract->active_to = $this->activeTo->endOfDay()->format('Y-m-d H:i:s');
        $contract->signed_at = $this->signedAt->startOfDay()->format('Y-m-d H:i:s');
        $contract->remaining_debt = Arr::get($this->data[0],'remainingDebt');
        $contract->premium = Arr::get($this->calcData['data'],'propertyPremium');

        $contract->program()->associate($this->program);
        $contract->company()->associate($this->company);
        $this->contract = $contract;

        return $contract->save();
    }

    /**
     * Сохранение полиса
     * @return bool
     */
    protected function save(): bool
    {
        static::log("Start to save Contract.");

        return $this->upsert(new Contracts());
    }

    /**
     * Получение результирующих данных
     * @return array
     */
    protected function getResultData(): array
    {
        $contract = $this->contract;

        return [
            "success" => true,
            "data" => [
                'contractId' => $contract->id,
                "propertyPolicyNumber" => 'ИПО-PROP-'.$contract->id,
                'propertyPremium' => $contract->premium,
            ]
        ];
    }

    public function getInsurancePremium(string $programCode, int $remainingDebt, bool $isWooden): int
    {
        $query = DB::table('programs')->where('program_code', $programCode)->first('matrix');
        $decodeMatrix = (json_decode($query->matrix, true));

        $woodenRate = $decodeMatrix['tariff']['wooden']['percent'] ?? 1;
        $stoneRate = $decodeMatrix['tariff']['stone']['percent'] ?? 1;

        if (!$isWooden) {
            return $remainingDebt * $stoneRate;
        }

        return $remainingDebt * $woodenRate;
    }
}
