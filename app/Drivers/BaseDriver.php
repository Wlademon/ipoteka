<?php

namespace App\Drivers;

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
use App\Services\SiteService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HigherOrderCollectionProxy;
use Strahovka\Payment\PayService;

/**
 * Class BaseDriver
 * @package App\Drivers
 */
abstract class BaseDriver implements IDriver
{
    use LoggerTrait;
    use PlugDriverTrait;

    /**
     * @var bool Указатель но успешность сохранения
     */
    protected bool $isSaved = false;

    /**
     * @var string|null Источник
     * @see BaseDriver::collectData()
     */
    protected ?string $trafficSource;

    /**
     * @var array|null Посчитанные данные
     * @see BaseDriver::collectData()
     */
    protected ?array $calcData;

    /**
     * Данные полученные из request
     * @see BaseDriver::createPolicy()
     * @var array|null
     */
    protected ?array $data;

    /**
     * Период действия полиса
     * @example ['15d', '15', 'd']
     * @see BaseDriver::resetActiveRange()
     * @var array|null
     */
    protected ?array $duration;

    protected ?Contracts $contract;
    protected ?Companies $company;
    protected ?Programs $program;
    protected ?Request $request;
    protected ?Carbon $activeFrom;
    protected ?Carbon $activeTo;
    protected ?Carbon $signedAt;
    protected ?Owners $owner;

    /**
     * Создание полиса
     * @param Request $request
     * @param Request $request
     * @return array
     */
    public function createPolicy(Request $request): array
    {
        $this->isSaved = false;
        $this->request = $request;
        $this->data = $this->request->all();
        $this->collectData();
        if ($this->validate()) {
            $this->resetActiveRange();
            if ($this->beforeSaveOrUpdate()) {
                $this->isSaved = $this->saveOrUpdate();
                $this->afterSaveOrUpdate();
            }
        }

        return $this->getResultData();
    }

    /**
     * Получение статуса полиса
     * @param Contracts $contract
     * @return string[]
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
     * Функция записи и обновления полиса
     * @param Contracts $contract
     * @return bool
     */
    protected function upsert(Contracts $contract): bool
    {
        $options = array_merge(
            $this->request->except(['object', 'subject']),
            ['trafficSource' => $this->trafficSource]
        );
        if (!empty($this->data['number'])) {
            $contract->number = $this->data['number'];
        } else {
            $contract->number = '';
        }
        $contract->options = json_encode($options, JSON_UNESCAPED_UNICODE);
        $contract->active_from = Carbon::parse($this->data['activeFrom'])->startOfDay()->format('Y-m-d H:i:s');
        $contract->active_to = $this->activeTo->endOfDay()->format('Y-m-d H:i:s');
        $contract->signed_at = $this->signedAt->startOfDay()->format('Y-m-d H:i:s');
        $contract->insured_sum = $this->data['insuredSum'];
        if (!empty($this->data['integrationId'])) {
            $contract->integration_id = $this->data['integrationId'];
        }
        $contract->premium = $this->calcData['premium'];
        $contract->calcCoeff = json_encode($this->calcData['calcCoeff'], JSON_UNESCAPED_UNICODE);

        $owner = Owners::where('code', Arr::get($this->data, 'ownerCode', 'STRAHOVKA'))->first(); // null if no records
        if ($owner) {
            $contract->owner()->associate($owner);
        }
        $contract->program()->associate($this->program);
        $contract->company()->associate($this->company);
        $this->contract = $contract;

        return $contract->save();
    }

    public function getPayLink(PayService $service, Contracts $contract, Request $request): array
    {
        // Не более 20 символов!
        $invoiceNum = sprintf("%s%03d%06d/%s", 'NS', $contract->company_id, $contract->id, Carbon::now()->format('His'));

        if (in_array(env('APP_ENV'), ['local', 'testing'])) {
            $invoiceNum = time() % 100 . $invoiceNum;
        }

        $data = [
            'successUrl' => env('STR_HOST', 'https://strahovka.ru') . $request->get('successUrl'),
            'failUrl' => env('STR_HOST', 'https://strahovka.ru') . $request->get('failUrl'),
            'phone' => str_replace([' ', '-'], '', $contract['subject']['phone']),
            'fullName' => $contract['subject_fullname'],
            'passport' => $contract['subject_passport'],
            'name' => "Полис по Телемед №{$contract->id}",
            'description' => "Оплата за полис {$contract->company->name} №{$contract->id}",
            'amount' => $contract['premium'],
            'merchantOrderNumber' => $invoiceNum,
        ];
        Log::info(__METHOD__ . '. Data for acquiring', [$data]);
        $response = $service->getPayLink($data);

        if (isset($response->errorCode) && $response->errorCode !== 0) {
            throw new \Exception($response->errorMessage . ' (code: ' . $response->errorCode . ')', 500);
        }

        return [
            'invoice_num' => $invoiceNum,
            'order_id' => $response->orderId,
            'form_url' => $response->formUrl,
        ];
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
     * Сохранение полиса
     * @return bool
     */
    protected function save(): bool
    {
        static::log("Start to save Contract.");

        return $this->upsert(new Contracts());
    }

    /**
     * Обновление полиса
     * @return bool
     * @throws \Exception
     */
    protected function update(): bool
    {
        $request = $this->request;
        static::log("Start to update Contract {$request['id']}.");
        $contract = Contracts::whereId($request['id'])->with('objects')->with('subject')->firstOrFail();
        $contract->subject->delete();
        foreach ($contract->objects as $object) {
            $object->delete();
        }

        return $this->upsert($contract);
    }

    /**
     * Сохранить данные полиса
     * @return bool
     * @throws \Exception
     */
    protected function saveOrUpdate(): bool
    {
        if (!empty($this->data['id'])) {
            return $this->update();
        }

        return $this->save();
    }

    /**
     * Функция вызываемая при удачном сохранении
     */
    protected function afterSaveOrUpdate(): void
    {
        $contract = $this->contract;
        foreach ($this->data['objects'] as $obj) {
            Log::info(__METHOD__ . ". getUserData object", [$obj]);
            $siteService = new SiteService();
            $code = md5($obj['lastName'] . $obj['firstName'] . ($obj['middleName'] ?? '') . $obj['birthDate'] . time());
            $obj['email'] = $code . '@strahovka.ru';
            $userObj = $siteService->getUserData($obj);
            if ($userObj) {
                $obj['login'] = Arr::get($userObj, 'login');
                $obj['subjectId'] = Arr::get($userObj, 'subjectId');
            }
            $object = new Objects(['value' => json_encode($obj, JSON_UNESCAPED_UNICODE)]);
            $object->contract()->associate($contract);
            $object->save();
        }
        $subject = new Subjects(['value' => json_encode($this->data['subject'], JSON_UNESCAPED_UNICODE)]);
        $subject->contract()->associate($contract);
        $subject->save();
    }

    /**
     * Сбор общих данных для дальнейших операций
     */
    protected function collectData(): void
    {
        $this->trafficSource = Helper::getTrafficSource($this->request);
        static::log('Start calculate');
        $this->activeFrom = Carbon::parse($this->data['activeFrom']);
        $this->activeTo = Carbon::parse($this->data['activeTo']);
        $this->signedAt = Carbon::now()->startOfDay();
        $this->calcData = $this->calculate($this->data);
        $this->program = Programs::with('company')->findOrFail($this->calcData['programId']);
        $this->company = $this->program->company;
        $ownerCode = Arr::get($this->data, 'ownerCode', 'STRAHOVKA');
        $this->owner = Owners::where('code', $ownerCode)->first();
        $siteService = new SiteService();
        $user = $siteService->getUserData($this->data['subject']);
        if ($user) {
            $this->data['subject']['login'] = Arr::get($user, 'login');
            $this->data['subject']['subjectId'] = Arr::get($user, 'subjectId');
        }
    }

    /**
     * Смещение времени активности полиса
     */
    protected function resetActiveRange(): void
    {
        $this->duration = $this->getCalcDuration();
        if ($this->duration[2] === 'd') {
            $validActiveTo = $this->activeFrom->addDays($this->duration[1])->subDays();
        } elseif($this->duration[2] === 'm') {
            $validActiveTo = $this->activeFrom->addMonths($this->duration[1])->subDays();
        } elseif($this->duration[2] === 'y') {
            $validActiveTo = $this->activeFrom->addYears($this->duration[1])->subDays();
        }
        if (isset($validActiveTo) && $this->activeTo != $validActiveTo) {
            $this->activeTo = $validActiveTo;
            static::log("Дата окончания полиса изменена на {$validActiveTo}");
        }
    }

    /**
     * Получить из входных данных длительность страхования
     * @return array|null
     */
    protected function getCalcDuration(): ?array
    {
        preg_match("/([0-9]+)([dmy]+)/", $this->calcData['duration'], $duration);

        return $duration;
    }

    /**
     * Получение результирующих данных
     * @return array
     */
    protected function getResultData(): array
    {
        $contract = $this->contract;

        return [
            'contractId' => $contract->id,
            'policyNumber' => $contract->number,
            'premiumSum' => $contract->premium,
        ];
    }
}
