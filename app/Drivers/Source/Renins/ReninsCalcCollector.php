<?php

namespace App\Drivers\Source\Renins;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

class ReninsCalcCollector implements Arrayable
{
    protected const TIME_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    protected array $data = [];

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

    public function setStartEnd($dateStart, $dateEnd): void
    {
        $this->data['dateBeg'] = $this->toTime($dateStart);
        $this->data['dateEnd'] = $this->toTime($dateEnd);
    }

    // только для life
    public function workStatus(array $subject)
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Статус занятости Страхователя',
            'code' => 'dogovor.strahStatusZanatosti',
            'type' => 'Строка',
            'stringValue' => WorkMatcher::match($subject['professions'] ?? [])
        ];
    }

    // только для life
    public function subjectIsObject(bool $is = true)
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Застрахованный является Страхователем',
            'code' => 'dogovor.zastrahYavlstrah',
            'type' => 'Логический',
            'boolValue' => $is ? 'true' : 'false'
        ];
    }

    // только property
    public function setBuildDate(int $year)
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Год постройки',
            'code' => 'dogovor.godPostroiki',
            'type' => 'Целое',
            'intValue' => $year
        ];
    }

    public function setBirthDate(string $date)
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

    // только для life
    public function setSex(int $sex): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Пол',
            'code' => 'dogovor.zastr1.fl.pol',
            'type' => 'Строка',
            'stringValue' => $sex ? 'жен.' : 'муж.'
        ];
    }

    public function setCreditCity(string $city): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Город выдачи кредита',
            'code' => 'dogovor.gorodVidachiKredita',
            'type' => 'Строка',
            'stringValue' => $city
        ];
    }

    public function setCreditSum(float $sum): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Сумма кредита',
            'code' => 'dogovor.ipotechnDogovor.summaKredita',
            'type' => 'Вещественный',
            'decimalValue' => $sum
        ];
    }

    public function setBankBik(string $bik): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'БИК',
            'code' => 'dogovor.ipotechnDogovor.bank.subekt.bik',
            'type' => 'Строка',
            'stringValue' => $bik
        ];
    }

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

    protected function toTime($date): ?string
    {
        if (is_numeric($date)) {
            return Carbon::createFromTimestamp($date)->format(self::TIME_FORMAT);
        }
        if (is_string($date)) {
            return Carbon::parse($date)->format(self::TIME_FORMAT);
        }
        if ($date instanceof \DateTime) {
            return $date->format(self::TIME_FORMAT);
        }

        return null;
    }

    public function toArray()
    {
        return [
            'productType' => 'Страхование имущества',
            'policyCalc' => $this->data
        ];
    }
}
