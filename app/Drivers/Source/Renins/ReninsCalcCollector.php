<?php

namespace App\Drivers\Source\Renins;

use DateTime;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * Class ReninsCalcCollector
 * @package App\Drivers\Source\Renins
 */
class ReninsCalcCollector implements Arrayable
{
    protected const TIME_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    protected array $data = [];

    /**
     * ReninsCalcCollector constructor.
     */
    public function __construct()
    {
        $this->data = [
            'insCompanyName' => 'АО «Группа Ренессанс Страхование»',
            'product' => [
                'name' => 'Коробочная Ипотека'
            ],
            'date' => $this->toTime(time()),
            'insurant' => [
                'type' => 'ФЛ',
            ],
            'insuranceObjects' => [
                'objects' => []
            ],
            'parameters' => [
                'parameters' => [
                    [
                        'name' => 'Валюта (спр)',
                        'code' => 'dogovor.ipotechnDogovor.currency.code',
                        'type' => 'Строка',
                        'stringValue' => 'RUR'
                    ],
                    [
                        'name' => 'Дата',
                        'code' => 'dogovor.ipotechnDogovor.data',
                        'type' => 'Дата',
                        'dateValue' => $this->toTime(time())
                    ],
                    [
                        'name' => 'Занимается экстремальными видами спорта из списка',
                        'code' => 'dogovor.extremSport',
                        'type' => 'Строка',
                        'stringValue' => 'нет'
                    ],
                    [
                        'name' => 'Условия труда связаны с повышенным риском для жизни и здоровья',
                        'code' => 'dogovor.usltrudaSvyazSRiskom',
                        'type' => 'Строка',
                        'stringValue' => 'нет',
                    ],
                    [
                        'name' => 'Вид деятельности на текущем месте работы отсутствует в списке',
                        'code' => 'dogovor.vidDeyatOtsutstvuet',
                        'type' => 'Логический',
                        'boolValue' => 'true',
                    ],

                ]
            ]
        ];
    }

    /**
     * @param $dateStart
     * @param $dateEnd
     */
    public function setContractStartEnd(string $policyStartDate, string $policyEndDate): void
    {
        $this->data['dateBeg'] = $this->toTime($policyStartDate);
        $this->data['dateEnd'] = $this->toTime($policyEndDate);
    }

    /**
     * @param array $subject
     */
    public function workStatus(array $subject): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Статус занятости Страхователя',
            'code' => 'dogovor.strahStatusZanatosti',
            'type' => 'Строка',
            'stringValue' => WorkMatcher::match($subject['professions'] ?? [])
        ];
    }

    /**
     * @param bool $is
     */
    public function subjectIsObject(bool $is = true): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Застрахованный является Страхователем',
            'code' => 'dogovor.zastrahYavlstrah',
            'type' => 'Логический',
            'boolValue' => $is ? 'true' : 'false'
        ];
    }

    /**
     * @param int $year
     */
    public function setBuildDate(int $year): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Год постройки',
            'code' => 'dogovor.godPostroiki',
            'type' => 'Целое',
            'intValue' => $year
        ];
    }

    /**
     * @param $phone
     */
    public static function getFormatPhone(string $phone): ?string
    {
        preg_match(
            '/(\d{1,4})\s*\(?(\d{3,5})\)?\s*(\d{3})[\s-]?(\d{2})[\s-]?(\d{2})/',
            $phone,
            $matches
        );
        if (count($matches) !== 6) {
            return null;
        }

        array_shift($matches);
        $phone = sprintf('+%s (%s) %s-%s-%s', ...$matches);

        return $phone;
    }

    /**
     * @param string $date
     */
    public function setBirthDate(string $date): void
    {
        $this->data['insurant']['physical'] = [
            'birthDate' => $this->toTime($date)
        ];
        $this->data['parameters']['parameters'][] = [
            'name' => 'Дата рождения',
            'code' => 'dogovor.zastr1.fl.dataRogd',
            'type' => 'Дата',
            'dateValue' => $this->toTime($date)
        ];
    }

    /**
     * @param int $sex
     */
    public function setSex(int $sex): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Пол',
            'code' => 'dogovor.zastr1.fl.pol',
            'type' => 'Строка',
            'stringValue' => $sex ? 'жен.' : 'муж.'
        ];
    }

    /**
     * @param string $city
     */
    public function setCreditCity(string $city): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Город выдачи кредита',
            'code' => 'dogovor.gorodVidachiKredita',
            'type' => 'Строка',
            'stringValue' => $city
        ];
    }

    /**
     * @param float $sum
     */
    public function setCreditSum(float $sum): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Сумма кредита',
            'code' => 'dogovor.ipotechnDogovor.summaKredita',
            'type' => 'Вещественный',
            'decimalValue' => $sum
        ];
    }

    /**
     * @param string $bik
     */
    public function setBankBik(string $bik): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'БИК',
            'code' => 'dogovor.ipotechnDogovor.bank.subekt.bik',
            'type' => 'Строка',
            'stringValue' => $bik
        ];
    }

    /**
     * @param array $risks
     * @param string $alias
     */
    public function addObject(array $risks, string $alias): void
    {
        $this->data['insuranceObjects']['objects'][] =[
            'name' => 'Объект страхования',
            'parameters' => null,
            'riskInfo' => [
                'risks' => $risks
            ],
            'alias' => $alias
        ];
    }

    /**
     * @param $date
     * @return string|null
     */
    protected function toTime($date): ?string
    {
        $result = null;
        if (is_numeric($date)) {
            $result = Carbon::createFromTimestamp($date)->format(self::TIME_FORMAT);
        } elseif (is_string($date)) {
            $result = Carbon::parse($date)->format(self::TIME_FORMAT);
        } elseif ($date instanceof DateTime) {
            $result = $date->format(self::TIME_FORMAT);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'productType' => 'Страхование имущества',
            'policyCalc' => $this->data
        ];
    }
}
