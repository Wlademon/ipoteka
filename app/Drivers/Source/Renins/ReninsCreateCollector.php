<?php

namespace App\Drivers\Source\Renins;

use Illuminate\Support\Arr;

/**
 * Class ReninsCreateCollector
 *
 * @package App\Drivers\Source\Renins
 */
class ReninsCreateCollector extends ReninsCalcCollector
{
    const CITIZENSHIP = 'Россия';
    const COUNTRY = 'Россия';
    const DOC_TYPE = 'ПАСПОРТ_РФ';
    const POST_INDEX_MOSCOW = '111000';
    const CREDIT_CITY = 'Москва';
    const CURRENCY = 'RUR';

    /**
     * ReninsCreateCollector constructor.
     */
    public function __construct()
    {
        parent::__construct();
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
                'stringValue' => self::CURRENCY,
            ],
            [
                'name' => 'Город выдачи кредита',
                'code' => 'dogovor.gorodVidachiKredita',
                'type' => 'Строка',
                'stringValue' => self::CREDIT_CITY,
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
                'stringValue' => 'нет',
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

    /**
     * @param         $dateStart
     * @param  float  $sum
     */
    public function setPayPlan(string $dateStart, float $sum): void
    {
        $this->data['paymentsPlan']['payments'][] = [
            'number' => 1,
            'date' => $this->toTime($dateStart),
            'sum' => $sum,
            'currency' => self::CURRENCY,
        ];
    }

    /**
     * @param  string  $state
     * @param  string  $city
     * @param  string  $street
     * @param  string  $house
     */
    public function setPropertyAddress(
        string $state,
        string $city,
        string $street,
        string $house
    ): void {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Строковое представление адреса',
            'code' => 'dogovor.adresImushestva.adresStroka',
            'type' => 'Строка',
            'stringValue' => implode(
                ', ',
                array_filter(
                    [
                        self::COUNTRY,
                        $state,
                        $city,
                        'ул.' . $street,
                        'д.' . $house,
                    ]
                )
            ),
        ];
    }

    /**
     * @param  string  $kladr
     */
    public function setKladr(string $kladr): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Код КЛАДР',
            'code' => 'dogovor.adresImushestva.region.kodKLADR',
            'type' => 'Строка',
            'stringValue' => $kladr,
        ];
    }

    /**
     * @param  string  $dateStart
     * @param  string  $dateEnd
     */
    public function setContractStartEnd(string $dateStart, string $dateEnd): void
    {
        $this->data['dateBeg'] = $this->toTime($dateStart);
        $this->data['dateEnd'] = $this->toTime($dateEnd);
    }

    /**
     * @param  float  $sum
     */
    public function setCreditSum(float $sum): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Страховая стоимость объекта',
            'code' => 'dogovor.strahStoimostObjekta',
            'type' => 'Вещественный',
            'decimalValue' => $sum,
        ];
        $this->data['parameters']['parameters'][] = [
            'name' => 'Сумма кредита',
            'code' => 'dogovor.ipotechnDogovor.summaKredita',
            'type' => 'Вещественный',
            'decimalValue' => $sum,
        ];
    }

    /**
     * @param  string  $number
     */
    public function setCreditNumber(string $number): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Номер',
            'code' => 'dogovor.ipotechnDogovor.nomer',
            'type' => 'Строка',
            'stringValue' => $number,
        ];
    }

    /**
     * @param  string  $date
     */
    public function setBirthDate(string $date): void
    {
        $this->data['parameters']['parameters'][] = [
            'name' => 'Дата рождения',
            'code' => 'dogovor.zastr1.fl.dataRogd',
            'type' => 'Дата',
            'dateValue' => $this->toTime($date),
        ];
    }

    /**
     * @param  string  $date
     */
    public function setBirthDateSubject(string $date): void
    {
        $this->data['insurant']['physical'] = array_merge(
            $this->data['insurant']['physical'],
            ['birthDate' => $this->toTime($date)]
        );
    }

    /**
     * @param  array  $subject
     */
    public function setHumanInfo(array $subject): void
    {
        $this->data['insurant']['physical'] = [
            'lastName' => Arr::get($subject, 'lastName'),
            'firstName' => Arr::get($subject, 'firstName'),
            'middleName' => Arr::get($subject, 'middleName'),
            'birthDate' => $this->toTime(Arr::get($subject, 'birthDate')),
            'email' => Arr::get($subject, 'email'),
            'phone' => self::getFormatPhone(Arr::get($subject, 'phone')),
            'sex' => Arr::get($subject, 'gender', 0) ? "F" : "M",
            'citizenship' => self::CITIZENSHIP,
            'document' => [
                'type' => self::DOC_TYPE,
                'series' => Arr::get($subject, 'docSeries'),
                'number' => Arr::get($subject, 'docNumber'),
                'placeOfIssue' => Arr::get($subject, 'docIssuePlace'),
                'dateOfIssue' => $this->toTime(Arr::get($subject, 'docIssueDate')),
                'kodPodrazd' => '',
            ],
            'factAddress' => [
                'country' => self::COUNTRY,
                'postIndex' => self::POST_INDEX_MOSCOW,
                'region' => trim(
                    str_replace(
                        [
                            'Республика',
                            'республика',
                            'рес',
                            ' р.',
                            'Область',
                            'область',
                            'обл',
                            ' о.',
                            'Край',
                            'край',
                            'край',
                            ' к.',
                        ],
                        '',
                        Arr::get($subject, 'state')
                    ),
                    ".\n\r, \t\0"
                ),
                'addressText' => implode(
                    ', ',
                    array_filter(
                        [
                            self::POST_INDEX_MOSCOW,
                            self::COUNTRY,
                            Arr::get($subject, 'city'),
                            'ул. ' . Arr::get($subject, 'street'),
                            Arr::get($subject, 'house'),
                        ]
                    )
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'policy' => $this->data,
        ];
    }
}
