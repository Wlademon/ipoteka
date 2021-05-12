<?php

namespace App\Drivers\Source\Renins;

class ReninsCreateCollector extends ReninsCalcCollector
{
    const CITIZENSHIP = 'Россия';
    const COUNTRY = 'Россия';
    const DOC_TYPE = 'ПАСПОРТ_РФ';
    const POST_INDEX_MOSCOW = '111000';
    const CREDIT_CITY = 'Москва';
    const CURRENCY = 'RUR';

    public function __construct()
    {
        parent::__construct();
        $this->data['onlinePayment'] = true;
        $this->data['currCode'] = self::CURRENCY;
        $this->data['parameters']['parameters'] = [
            [
                'name' => 'Доп.вопрос 3',
                'code' => 'dogovor.dopVopros3',
                'type' => 'Логический',
                'boolValue' => 'true',
            ],
            [
                'name' => 'Доп.вопрос 2',
                'code' => 'dogovor.dopVopros2',
                'type' => 'Логический',
                'boolValue' => 'true',
            ],
            [
                'name' => 'Доп.вопрос 1',
                'code' => 'dogovor.dopVopros1',
                'type' => 'Логический',
                'boolValue' => 'true',
            ],
            [
                'name' => 'Валюта (спр)',
                'code' => 'dogovor.ipotechnDogovor.currency.code',
                'type' => 'Строка',
                'boolValue' => self::CURRENCY,
            ],
            [
                'name' => 'Город выдачи кредита',
                'code' => 'dogovor.gorodVidachiKredita',
                'type' => 'Строка',
                'stringValue' => self::CREDIT_CITY,
            ],
            [
                'name' => 'Название',
                'code' => 'dogovor.adresImushestva.strana.name',
                'type' => 'Строка',
                'stringValue' => self::COUNTRY,
            ],
            [
                'name' => 'Дата',
                'code' => 'dogovor.ipotechnDogovor.data',
                'type' => 'Дата',
                'dateValue' => $this->toTime('now'),
            ],
            [
                'name' => 'Объект недвижимого имущества',
                'code' => 'dogovor.tipimushestva',
                'type' => 'Строка',
                'stringValue' => 'Квартира',
            ],
            [
                'name' => 'Вопрос А01',
                'code' => 'dogovor.voprA01',
                'type' => 'Логический',
                'boolValue' => 'true',
            ],
            [
                'name' => 'Адрес имущества совпадает с адресом страхователя',
                'code' => 'dogovor.adresImushSovpad',
                'type' => 'Логический',
                'boolValue' => 'false',
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
                'name' => 'Страхователь подтверждает данные, указанные в Анкете о состоянии здоровья',
                'code' => 'dogovor.soglasieSAnketoi',
                'type' => 'Логический',
                'boolValue' => 'true',
            ],
            [
                'name' => 'Вид деятельности на текущем месте работы отсутствует в списке',
                'code' => 'dogovor.vidDeyatOtsutstvuet',
                'type' => 'Логический',
                'boolValue' => 'true',
            ],
        ];
    }

    public function setPayPlan($dateStart, float $sum)
    {
        $this->data['paymentsPlan']['payments'][] = [
            'number' => 1,
            'date' => $this->toTime($dateStart),
            'sum' => $sum,
            'currency' => self::CURRENCY
        ];
    }

    public function setPropertyAddress($state, $city, $street, $house)
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Регион объекта',
            'code' => 'dogovor.adresImushestva.region.name',
            'type' => 'Строка',
            'stringValue' => $state,
        ];
        $this->data['parameters']['parameters'][] = [
            'name' => 'Код КЛАДР',
            'code' => 'dogovor.adresImushestva.region.kodKLADR',
            'type' => 'Строка',
            'stringValue' => implode(', ', array_filter([self::COUNTRY, $state, $city, $street, $house])),
        ];
    }

    public function setKladr(string $kladr)
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Код КЛАДР',
            'code' => 'dogovor.adresImushestva.region.kodKLADR',
            'type' => 'Строка',
            'stringValue' => $kladr,
        ];
    }

    public function setStartEnd($dateStart, $dateEnd): void
    {
        $this->data['dateBeg'] = $this->toTime($dateStart);
        $this->data['dateEnd'] = $this->toTime($dateEnd);
    }

    public function setCreditSum(float $sum): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Страховая стоимость объекта',
            'code' => 'dogovor.strahStoimostObjekta',
            'type' => 'Вещественный',
            'decimalValue' => 3000000,
        ];
        $this->data['parameters']['parameters'][] = [
            'name' => 'Сумма кредита',
            'code' => 'dogovor.ipotechnDogovor.summaKredita',
            'type' => 'Вещественный',
            'decimalValue' => 3000000,
        ];
    }

    public function setCreditNumber(string $number)
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Номер',
            'code' => 'dogovor.ipotechnDogovor.nomer',
            'type' => 'Строка',
            'decimalValue' => $number,
        ];
    }

    public function setHumanInfo(array $subject)
    {
        $this->data['insurant']['physical'] = [
            'lastName' => \Arr::get($subject, 'lastName'),
            'firstName' => \Arr::get($subject, 'firstName'),
            'middleName' => \Arr::get($subject, 'middleName'),
            'birthDate' => $this->toTime(\Arr::get($subject, 'birthDate')),
            'email' => \Arr::get($subject, 'email'),
            'phone' => \Arr::get($subject, 'phone'),
            'sex' => \Arr::get($subject, 'gender', 0) ? "F" : "M",
            'citizenship' => self::CITIZENSHIP,
            'document' => [
                'type' => self::DOC_TYPE,
                'series' => \Arr::get($subject, 'docSeries'),
                'number' => \Arr::get($subject, 'docNumber'),
                'placeOfIssue' => \Arr::get($subject, 'docIssuePlace'),
                'dateOfIssue' => $this->toTime(\Arr::get($subject, 'docIssuePlace')),
                'kodPodrazd' => '',
            ],
            'factAddress' => [
                'country' => self::COUNTRY,
                'postIndex' => self::POST_INDEX_MOSCOW,
                'region' => \Arr::get($subject, 'state'),
                'addressText' => implode(
                    ', ',
                    array_filter([
                        self::POST_INDEX_MOSCOW,
                        self::COUNTRY,
                        \Arr::get($subject, 'state'),
                        \Arr::get($subject, 'city'),
                        \Arr::get($subject, 'street'),
                        \Arr::get($subject, 'house'),
                    ])
                ),
            ],
        ];
    }



    public function toArray()
    {
        return [
            'policy' => $this->data
        ];
    }
}
