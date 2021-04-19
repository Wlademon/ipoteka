<?php

namespace App\Drivers;

use App\Drivers\Source\Renissans\DataCollector;
use App\Drivers\Source\Renissans\TokenService;
use App\Drivers\Traits\PrintPdfTrait;
use App\Exceptions\Drivers\ReninsDriverException;
use App\Models\Contracts;
use App\Models\Programs;
use App\Services\HttpClientService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Strahovka\Payment\PayService;

/**
 * Class RenisansDriver
 * @package App\Drivers
 */
class ReninsDriver extends BaseDriver
{
    use PrintPdfTrait;

    const URL_CALCULATE = '/Link2DMS/1.0/dms/calculate';
    const URL_PRINT = '/Link2DMS/1.0/dms/print';
    const URL_SAVE = '/Link2DMS/1.0/dms/save';
    const URL_PAY = '/Link2DMS/1.0/dms/getPaymentLink';
    const URL_ISSUE = '/Link2DMS/1.0/dms/issue';
    const CALC_TEMPLATE = [
        'messageId' => '',
        'save' => true,
        'policy' => [
            'products' => [
                [
                    'code' => '',
                    'packets' => [
                        [
                            'code' => 'Packet 1',
                            'covers' => [
                                [
                                    'code' => 'DMS-PR1',
                                    'sum' => 250000.00
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            'parts' => [
                'general' => [
                    'duration' => [
                        'code' => '',
                        'startDate' => '',
                    ],
                    'insuranceTerritoryCode' => 'Вся территория РФ',
                ],
                'additionalConditions' => [
                    'ageGroups' => [],
                ],
            ],
            'subjects' => [
                [
                    'type' => 'Individual',
                    'role' => 'Policeholder',
                ]
            ],
        ],
    ];

    /** @var HttpClientService */
    protected HttpClientService $httpClient;

    protected $collectCalcData = [];

    /**
     * RenisansDriver constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $host = config('sc.renisans.host');
        $login = config('sc.renisans.login');
        $pass = config('sc.renisans.pass');
        $token = TokenService::getToken($host, $login, $pass);
        if (!$host) {
            self::abortLog('Not set Renisans host.', ReninsDriverException::class);
        }
        if (!$token) {
            self::abortLog('Not set Renisans token.', ReninsDriverException::class);
        }
        $this->httpClient = HttpClientService::create(
            $host,
            [
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ]
            ],
        );
    }

    /**
     * @return string
     */
    public static function generateMessageId(): string
    {
        return (string)\Str::uuid();
    }

    /**
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function calculate(array $data): array
    {
        $program = Programs::active()
            ->whereProgramCode($data['programCode'])
            ->where('insured_sum', '>=', $data['insuredSum'])
            ->orderBy('insured_sum')
            ->first();
        if (!$program) {
            self::abortLog('Program not found for data', ReninsDriverException::class);
        }
        self::log("Found Program with ID {$program->id}");
        // start out calc
        $result = $this->httpClient->sendJson(
            self::URL_CALCULATE,
            $this->collectCalcData = $this->collectCalcData($program, $messId)
        );
        if (!$result) {
            throw new ReninsDriverException($this->httpClient->getLastError());
        }
        $this->findError($result);
        $this->data['integrationId'] = $result['accountNumber'];
        $this->data['insuredSum'] = $result['calculationResult']['bonus'];
        // finish out calc
        $conditions = $program->conditions;
        $objectsCount = count($data['objects']);
        if ($objectsCount > $conditions->maxInsuredCount) {
            self::abortLog(
                "Количество страхуемых {$objectsCount} больше максимального возможного {$conditions->maxInsuredCount}",
                ReninsDriverException::class
            );
        }
        $duration = self::getDuration($data);
        $calcCoeff['insuredSum'] = $program->insuredSum;
        $calcCoeff['fullPremium'] = $result['calculationResult']['bonus'];
        return [
            'premium' => $result['calculationResult']['bonus'],
            'duration' => $duration . 'm',
            'insuredSum' => $program->insuredSum,
            'programId' => $program->id,
            'calcCoeff' => $calcCoeff,
        ];
    }

    /**
     * @return bool
     * @throws ValidationException
     */
    protected function beforeSaveOrUpdate(): bool
    {
        if (empty($this->data['integrationId'])) {
            $this->calculate($this->data);
        }

        return $this->outSave();
    }

    /**
     * @return bool
     * @throws ValidationException
     */
    protected function outSave(): bool
    {
        $this->collectCalcData['policy']['accountNumber'] = $this->data['integrationId'];
        if (!empty($this->data['number']) && !empty($this->data['insuredSum'])) {
            return true;
        }

        $sendData = [
            'messageId' => self::generateMessageId(),
            'policy' => [
                'parts' => [
                    'general' => [
                        'duration' => [
                            'issueDate' => self::inToDateFormat(new Carbon())
                        ],
                        'paymentForm' => 'онлайн-оплата',
                        'paymentMethod' => 'платежная карта',
                        'amountOfCash' => $this->calcData['premium']
                    ]
                ],
                'subjects' => [
                    $this->dataOfSubject()
                ],
                'insuranceObjects' => $this->dataOfObjects(),
                'accountNumber' => $this->data['integrationId']
            ]
        ];

        $result = $this->httpClient->sendJson(
            self::URL_SAVE,
            $sendData
        );
        if (!$result) {
            throw new ReninsDriverException($this->httpClient->getLastError());
        }
        $this->findError($result);
        $this->data['number'] = $result['policyNumber'];
        $this->data['insuredSum'] = $result['calculationResult']['bonus'];

        return true;
    }

    public function getPayLink(PayService $service, Contracts $contract, Request $request): array
    {
        $sendData = [
            'messageId' => self::generateMessageId(),
            'accountNumber' => $contract->integration_id,
            'successUrl' => env('STR_HOST', 'https://strahovka.ru') . $request->get('successUrl'),
            'failUrl' => env('STR_HOST', 'https://strahovka.ru') . $request->get('failUrl'),
        ];
        $invoiceNum = sprintf("%s%03d%06d/%s", 'NS', $contract->company_id, $contract->id, Carbon::now()->format('His'));

        if (in_array(env('APP_ENV'), ['local', 'testing'])) {
            $invoiceNum = time() % 100 . $invoiceNum;
        }

        $result = $this->httpClient->sendJson(
            self::URL_PAY,
            $sendData
        );
        if (!$result) {
            if (is_array($this->httpClient->getLastError())) {
                throw new ReninsDriverException($this->httpClient->getLastError());
            }
            throw new ReninsDriverException([$this->httpClient->getLastError()]);
        }
        $this->findError($result);
        parse_str(parse_url($result['url'],PHP_URL_QUERY), $out);

        return [
            'invoice_num' => $invoiceNum,
            'order_id' => $out['mdOrder'],
            'form_url' => $result['url'],
        ];
    }

    /**
     * @return array
     */
    protected function dataOfSubject(): array
    {
        $subject = collect($this->data['subject']);

        return [
            'type' => 'Individual',
            'role' => 'Policeholder',
            'addresses' => [
                [
                    'type' => 'RegistrationAddress',
                    'county' => '',
                    'country' => 'Россия',
                    'region' => $subject->get('state', ''),
                    'city' => $subject->get('city', ''),
                    'street' => $subject->get('street', ''),
                    'building' => $subject->get('house', ''),
                    'corps' => $subject->get('block', ''),
                    'construction' => '',
                    'apartment' => $subject->get('apartment', ''),
                    'zipCode' => '',
                ]
            ],
            'identityDocuments' => [
                [
                    'documentType' => 'Passport',
                    'series' => $subject->get('docSeries', ''),
                    'number' => $subject->get('docNumber', ''),
                    'issuedBy' => $subject->get('docSeries', ''),
                    'issueDate' => self::inToDateFormat($subject->get('docIssueDate')),
                ],
            ],
            'dateOfBirth' => self::inToDateFormat($subject->get('birthDate')),
            'citizenship' => 'Россия',
            'email' => $subject->get('email'),
            'phone' => $subject->get('phone'),
            'name' => $subject->get('firstName'),
            'lastName' => $subject->get('lastName'),
            'patronymic' => $subject->get('middleName'),
            'sex' => (int)$subject->get('gender'),
            'companyName' => '',
            'inn' => '',
            'ogrn' => '',
        ];
    }

    /**
     * @param string $date
     * @param string|null $default
     * @return string|null
     */
    protected static function inToDateFormat(string $date, ?string $default = null): string
    {
        if (!$date) {
            return $default;
        }

        $dateMsec = (new \DateTime())->format('v');
        return Carbon::parse($date)->format('Y-m-d\\TH:i:s.') . (int)mb_strcut($dateMsec, 0, 3) . 'Z';
    }

    /**
     * @return array
     */
    protected function dataOfObjects(): array
    {
        $objects = $this->data['objects'];
        $sendObjects = [];
        foreach ($objects as $object) {
            $object = collect($object);
            $sendObjects[] = [
                'code' => 'person',
                'person' => [
                    'name' => $object->get('firstName'),
                    'lastName' => $object->get('lastName'),
                    'patronymic' => $object->get('middleName'),
                    'dateOfBirth' => self::inToDateFormat($object->get('birthDate'), ''),
                    'sex' => $object->get('gender') ? 'Female' : 'Male',
                    'citizenship' => 'Россия',
                    'identityDocument' => [
                        'documentType' => 'Passport',
                        'series' => $object->get('docSeries'),
                        'number' => $object->get('docNumber'),
                        'issuedBy' => $object->get('docIssuePlace'),
                        'issueDate' => self::inToDateFormat($object->get('docIssueDate'), ''),
                    ],
                    'phone' => $object->get('phone'),
                ],
                'addresses' => [
                    [
                        'type' => 'RegistrationAddress',
                        'country' => 'Россия',
                        'city' => $object->get('city'),
                        'street' => $object->get('street'),
                        'apartment' => $object->get('house')
                    ]
                ]
            ];
        }

        return $sendObjects;
    }

    /**
     * @param $data
     * @throws ValidationException
     */
    protected function findError($data): void
    {
        if (!$data['success'] && !empty($data['messages'])) {
            $messages = array_column($data['messages'], 'text');
            throw new ReninsDriverException(current($messages));
        }
    }

    /**
     * @param Programs $program
     * @param null $messageId
     * @return array
     * @throws \Exception
     */
    protected function collectCalcData(Programs $program, &$messageId = null): array
    {
        $sendData = self::CALC_TEMPLATE;
        $sendData['messageId'] = self::generateMessageId();
        $sendData['policy']['products'][0]['code'] = 'TELEMED';
        $sendData['policy']['parts']['general']['duration'] = [
            'code' => self::getDuration($this->data),
            'startDate' => $this->activeFrom->endOfDay()->format('Y-m-d\TH:i:s.000\Z'),
        ];
        $diffDate = $this->activeFrom->endOfDay()->diff((new Carbon())->endOfDay());
        if ($diffDate->d > 30 || $diffDate->d < 2 || $diffDate->m || $diffDate->y) {
            self::abortLog('Дата начала действия полиса должна быть в интервале от текущая дата +1 день до текущая дата +30 дней');
        }

        $this->collectObjects($program, $sendData);

        return $sendData;
    }

    /**
     * @param Programs $program
     * @param array $sendData
     * @throws \Exception
     */
    protected function collectObjects(Programs $program, array &$sendData): void
    {
        $covers = [];
        $ageGroups = [];
        $conditions = $program->conditions;
        foreach ($this->data['objects'] as $object) {
            $birthDate = Carbon::parse($object['birthDate']);
            $age = $birthDate->floatDiffInYears(Carbon::today());
            if ($age < $conditions->minAges) {
                self::abortLog("Возраст одного из застрахованных меньше допустимого в программе {$conditions->minAges}");
            }
            if ($age > $conditions->maxAges) {
                self::abortLog("Возраст одного из застрахованных больше допустимого в программе {$conditions->maxAges}");
            }
            $cover = DataCollector::choice($this->data['programCode'], $age, $ageGroup);
            if (!$cover) {
                self::abortLog("Программа не подходит для лиц данного возраста: {$age} лет");
            }
            if (!isset($covers[$cover])) {
                $covers[$cover] = 0;
            }
            if (!isset($ageGroups[$ageGroup])) {
                $ageGroups[$ageGroup] = 0;
            }
            $ageGroups[$ageGroup]++;
            $covers[$cover]++;
        }

        foreach ($ageGroups as $ageGroup => $count) {
            $sendData['policy']['parts']['additionalConditions']['ageGroups'][] = [
                'code' => $ageGroup,
                'count' => $count
            ];
        }
    }

    /**
     * @param Contracts $contract
     * @throws ReninsDriverException
     */
    public function triggerGetLink(Contracts $contract): void
    {
        $result = $this->httpClient->sendJson(self::URL_ISSUE, [
            'messageId' => self::generateMessageId(),
            'accountNumber' => $contract->integration_id,
        ]);
        if (!$result) {
            if (is_array($this->httpClient->getLastError())) {
                throw new ReninsDriverException($this->httpClient->getLastError());
            }
            throw new ReninsDriverException([$this->httpClient->getLastError()]);
        }
        $this->findError($result);
    }

    /**
     * @param $data
     * @return int
     * @throws \Exception
     */
    protected static function getDuration($data): int
    {
        $activeFrom = Carbon::parse($data['activeFrom']);
        $activeTo = Carbon::parse($data['activeTo'])->addDays(1);

        $diffInMonth = $activeTo->diffInMonths($activeFrom);
        $diffInDays = $activeTo->diffInDays($activeFrom->addMonths($diffInMonth));
        if ($diffInMonth == 0) {
            $duration = 1;
        } else {

            $months = $diffInMonth + ($diffInDays > 0 ? 1 : 0);
            if ($months > 12) {
                self::abortLog('Длительность полиса больше 12m');
            }
            $duration = $months;
        }

        return $duration;
    }

    /**
     * @param Contracts $contract
     * @param bool $sample
     * @param bool $reset
     * @param string|null $filePath
     * @return string
     * @throws \Exception
     */
    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null): string
    {
        $sampleText = $sample ? '_sample' : '';
        if(!$filePath) {
            $filename = config('ns.pdf.path') . sha1($contract->id . $contract->number) . $sampleText . '.pdf';
        } else {
            $filename = ltrim($filePath, '/');
        }
        $filenameWithPath = public_path($filename);

        if (!file_exists($filenameWithPath) || $reset) {
            $result = $this->httpClient->sendJson(self::URL_PRINT, [
                'messageId' => self::generateMessageId(),
                'accountNumber' => $contract->integration_id,
                'documentTypeCode' => 'OriginalPolicy',
                'documentLabels' => [
                    "HasStamp",
                    "OnlinePayment_OriginalPolicy_Attachment"
                ]
            ]);
            if (
                empty($result['success']) ||
                !filter_var($result['success'], FILTER_VALIDATE_BOOL) ||
                empty($result['url'])
            ) {
                self::abortLog("Не удалось получить ссылку документ: {$contract->integration_id}.", ReninsDriverException::class);
            }
            if (!($content = file_get_contents($result['url']))) {
                self::abortLog("Не удалось получить документ: {$contract->integration_id}.", ReninsDriverException::class);
            }
            if (!file_put_contents($filenameWithPath, $content)) {
                self::abortLog("Не удалось записать документ: {$contract->integration_id}.", ReninsDriverException::class);
            }
        }

        return self::generateBase64($filenameWithPath);
    }
}
