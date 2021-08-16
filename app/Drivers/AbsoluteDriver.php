<?php


namespace App\Drivers;


use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLink;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Traits\DriverTrait;
use App\Models\Contract;
use App\Services\PayService\PayLinks;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService;
use PHPUnit\Framework\MockObject\RuntimeException;
use GuzzleHttp\Client;
use PHPUnit\Util\Exception;
use Psy\Util\Str;

class AbsoluteDriver implements DriverInterface, LocalPaymentDriverInterface
{

    use DriverTrait;

    /**
     * @inheritDoc
     */
    protected $baseUrl = 'https://represtapi.absolutins.ru/ords/rest';
    protected $accessToken;
    protected $ClientID = 'Wpsa0QvBoyjwUMQYJ6707A..';
    protected $ClientSecret = 'waSVo19oyiyd78T-QCMxIw..';
    protected $paymentService;
    protected $pdfpath = 'ab/pdf/';
    protected Client $client;

    const CALCULATE_LIFE_PATH = '/api/mortgage/sber/life/calculation/create';
    const CALCULATE_PROPERTY_PATH = '/api/mortgage/sber/property/calculation/create';
    const LIFE_AGREEMENT_PATH = '/api/mortgage/sber/life/agreement/create';
    const PROPERTY_AGREEMENT_PATH = '/api/mortgage/sber/property/agreement/create';
    const PRINT_POLICY_PATH = '/api/print/agreement/';
    const RELEASED_POLICY_PATH = '/api/agreement/set/released/';
    const ADDRESS_CODE_2247 = 2247;
    const ADDRESS_CODE_2246 = 2246;
    const CONTACT_CODE_2243 = 2243;
    const CONTACT_CODE_2240 = 2240;
    const DOCUMENT_CODE_1165 = 1165;
    const IS_LIFE = 'ABSOLUT_MORTGAGE_003_01';
    const IS_PROPERTY = 'ABSOLUT_MORTGAGE_001_01';
    const IS_PROPERTY_AND_LIFE = 'ABSOLUT_MORTGAGE_002_01';


    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->client = new Client();
        $this->paymentService = new PaymentService($repository->get($prefix . 'pay_host'));
        $this->accessToken = $this->getToken();
    }

    protected function getToken()
    {
        $data = ['grant_type' => 'client_credentials'];
        $response = Http::withBasicAuth($this->ClientID, $this->ClientSecret)
            ->asForm()
            ->post($this->baseUrl . '/oauth/token', $data);

        if (!$response->getStatusCode() == 200) {
            throw new Exception('Error get token');
        }

        $decodeResponse = json_decode($response->getBody()->getContents(), true);

        if (!Arr::has($decodeResponse, 'access_token')) {
            throw new Exception('Response has not access_token');
        }
        return $decodeResponse['access_token'];
    }

    public static function decodeResponse($response, $validateFields)
    {
        $decodeResponse = json_decode($response->getBody()->getContents(), true);
        $validator = Validator::make($decodeResponse, $validateFields);

        if (!$validator->validated()) {
            throw new Exception("Error validate for {$validateFields}");
        }
        return $decodeResponse;
    }

    public function post($data, $path, $validateFields)
    {
        try {
            $response = $this->client->post(
                $this->baseUrl . $path,
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->accessToken}",
                    ],
                    'json' => $data,
                ]
            );
            $decodeResponse = $this::decodeResponse($response, $validateFields);
            return $decodeResponse;
        } catch (GuzzleException $e) {
            throw new RuntimeException("POST request exception from {$path} {$e->getMessage()}");
        }
    }

    public function put($path, $validateFields, $data = null)
    {
        try {
            $response = $this->client->put($this->baseUrl . $path, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ]
            ]);
            $decodeResponse = $this::decodeResponse($response, $validateFields);
            return $decodeResponse;
        } catch (GuzzleException $e) {
            throw new RuntimeException("PUT request exception from {$path} {$e->getMessage()}");
        }
    }

    public function get($path, $validateFields)
    {
        try {
            $response = $this->client->get($this->baseUrl . $path, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ]
            ]);
            $decodeResponse = $this::decodeResponse($response, $validateFields);
            return $decodeResponse;
        } catch (GuzzleException $e) {
            throw new RuntimeException("Put request exception from {$path} {$e->getMessage()}");
        }
    }

    public static function isLife($data): bool
    {
        return in_array(self::IS_LIFE, $data);
    }

    public static function isProperty($data): bool
    {
        return in_array(self::IS_PROPERTY, $data);
    }

    public static function isPropertyAndLife($data): bool
    {
        return in_array(self::IS_PROPERTY_AND_LIFE, $data);
    }

    public static function getGender($data)
    {
        return $data['objects']['life']['gender'] == 0 ? 'М' : 'Ж';
    }

    public function calculate(array $data): CalculatedInterface
    {
        $life = 0;
        $property = 0;
        $validateFileds = [
            'result' => 'required',
            'result.*.data' => 'required',
            'result.*.data.*.premium_sum' => 'required',
        ];

        // Возможны три варианта страхования ABSOLUT_MORTGAGE_003_01 (Жизнь); ABSOLUT_MORTGAGE_001_01 (Имущество); ABSOLUT_MORTGAGE_002_01 (Жизнь + имущество)
        if ($this::isLife($data) || $this::isPropertyAndLife($data)) {
            $body = [
                'limit_sum' => $data['remainingDebt'],
                'sex' => $this::getGender($data),
                'birthday' => $data['objects']['life']['birthDate'],
            ];
            $path = self::CALCULATE_LIFE_PATH;
            $life = $this->post($body, $path, $validateFileds)['result']['data']['premium_sum'];
        }
        if ($this::isProperty($data) || $this::isPropertyAndLife($data)) {
            $body = [
                'limit_sum' => $data['remainingDebt'],
            ];
            $path = self::CALCULATE_PROPERTY_PATH;
            $property = $this->post($body, $path, $validateFileds)['result']['data']['premium_sum'];
        }
        $result = [
            'life' => $life,
            'property' => $property,
        ];
        return new Calculated($data['isn'] ?? null, $result['life'] ?? null, $result['property'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getPayLink(Contract $contract, PayLinks $payLinks): PayLinkInterface
    {
        $urls = [
            'success' => $payLinks->getSuccessUrl(),
            'fail' => $payLinks->getFailUrl(),
        ];

        switch ($contract->options['programCode']) {
            case self::IS_PROPERTY_AND_LIFE:
                $array = [
                    [
                        'price' => $contract->options['price']['priceLife'],
                        'isn' => $contract->options['isn']['isnLife'],
                        'description' => 'Жизнь',
                    ],
                    [
                        'price' => $contract->options['price']['priceProperty'],
                        'isn' => $contract->options['isn']['isnProperty'],
                        'description' => 'Имущество',
                    ],
                ];
                break;
            case self::IS_PROPERTY:
                $array = [
                    [
                        'price' => $contract->options['price']['priceProperty'],
                        'isn' => $contract->options['isn']['isnProperty'],
                        'description' => 'Имущество',
                    ],
                ];
                break;
            case self::IS_LIFE:
                $array = [
                    [
                        'price' => $contract->options['price']['priceLife'],
                        'isn' => $contract->options['isn']['isnLife'],
                        'description' => 'Жизнь',
                    ],
                ];
                break;
        }

        $result = $this->paymentService->payLink($contract, $urls, $array);

        return new PayLink(
            $result['orderId'], $result['url'], $contract->remainingDebt
        );
    }

    /**
     * @inheritDoc
     */

    public static function getSubjectAddress($data)
    {
        $arr = [
            $data['subject']['state'],
            $data['subject']['city'],
            $data['subject']['street'],
            $data['subject']['house'],
            $data['subject']['block'],
            $data['subject']['apartment'],
        ];
        return implode(', ', $arr);
    }


    public static function getObjectPropertyAddress($data)
    {
        $arr = [
            $data['objects']['property']['state'],
            $data['objects']['property']['city'],
            $data['objects']['property']['street'],
            $data['objects']['property']['house'],
            $data['objects']['property']['block'],
            $data['objects']['property']['apartment'],
        ];
        return implode(', ', $arr);
    }

    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface
    {
        if (self::isLife($data) || self::isPropertyAndLife($data)) {
            $body = [
                'date_begin' => $data['activeFrom'],
                'agr_credit_number' => $data['mortgageAgreementNumber'],
                'agr_credit_date_conc' => $data['activeTo'],
                'limit_sum' => $data['remainingDebt'],
                'policy_holder' => [
                    'lastname' => $data['objects']['life']['lastName'],
                    'firstname' => $data['objects']['life']['firstName'],
                    'parentname' => $data['objects']['life']['middleName'],
                    'sex' => $data['objects']['life']['gender'] == 0 ? 'М' : 'Ж',
                    'birthday' => $data['objects']['life']['birthDate'],
                    'address' => [
                        [
                            'code' => self::ADDRESS_CODE_2247,
                            'code_desc' => '',
                            'text' => self::getSubjectAddress($data),
                            'fias_id' => '',
                        ],
                        [
                            'code' => self::ADDRESS_CODE_2246,
                            'code_desc' => '',
                            'text' => self::getSubjectAddress($data),
                        ],
                    ],
                    'contact' => [
                        [
                            'code' => self::CONTACT_CODE_2243,
                            'code_desc' => 'E-mail',
                            'text' => $data['subject']['email'],
                        ],
                        [
                            'code' => self::CONTACT_CODE_2240,
                            'text' => $data['subject']['phone'],
                        ],
                    ],
                    'document' => [
                        'code' => self::DOCUMENT_CODE_1165,
                        'series' => $data['subject']['docSeries'],
                        'number' => $data['subject']['docNumber'],
                        'issue_date' => $data['subject']['docIssueDate'],
                        'issue_by' => $data['subject']['docIssuePlace'],
                    ],
                ],
            ];

            $path = self::LIFE_AGREEMENT_PATH;

            $validateFields = [
                'result' => 'required',
                'result.*.data' => 'required',
                'result.*.data.*.premium_sum' => 'required',
                'result.*.data.*.isn' => 'required',
                'result.*.data.*.policy_no' => 'required',
            ];

            $response = $this->post($body, $path, $validateFields);

            $life = $response['result']['data']['premium_sum'];

            $policyIdLife = $response['result']['data']['isn'];
            $policyNumberLife = $response['result']['data']['policy_no'];
        }

        if ($this::isProperty($data) || $this::isPropertyAndLife($data)) {
            $body = [
                'date_begin' => $data['activeFrom'],
                'agr_credit_number' => $data['mortgageAgreementNumber'],
                'agr_credit_date_conc' => $data['activeTo'],
                'limit_sum' => $data['remainingDebt'],
                'ins_object' => [
                    'address' => [
                        'code' => self::ADDRESS_CODE_2247,
                        'code_desc' => '',
                        'text' => self::getObjectPropertyAddress($data),
                        'fias_id' => '',
                    ],
                ],
                'policy_holder' => [
                    'lastname' => $data['objects']['life']['lastName'],
                    'firstname' => $data['objects']['life']['firstName'],
                    'parentname' => $data['objects']['life']['middleName'],
                    'sex' => $data['objects']['life']['gender'] == 0 ? 'М' : 'Ж',
                    'birthday' => $data['objects']['life']['birthDate'],
                    'address' => [
                        [
                            'code' => self::ADDRESS_CODE_2247,
                            'code_desc' => '',
                            'text' => self::getSubjectAddress($data),
                            'fias_id' => '',
                        ],
                        [
                            'code' => self::ADDRESS_CODE_2246,
                            'code_desc' => '',
                            'text' => self::getSubjectAddress($data),
                        ],
                    ],
                    'contact' => [
                        [
                            'code' => self::CONTACT_CODE_2243,
                            'code_desc' => 'E-mail',
                            'text' => $data['subject']['email'],
                        ],
                        [
                            'code' => self::CONTACT_CODE_2240,
                            'text' => $data['subject']['phone'],
                        ],
                    ],
                    'document' => [
                        'code' => self::DOCUMENT_CODE_1165,
                        'series' => $data['subject']['docSeries'],
                        'number' => $data['subject']['docNumber'],
                        'issue_date' => $data['subject']['docIssueDate'],
                        'issue_by' => $data['subject']['docIssuePlace'],
                    ],
                ],
            ];

            $path = self::PROPERTY_AGREEMENT_PATH;
            $validateFields = [
                'result' => 'required',
                'result.*.data' => 'required',
                'result.*.data.*.premium_sum' => 'required',
                'result.*.data.*.isn' => 'required',
                'result.*.data.*.policy_no' => 'required',
            ];
            $response = $this->post($body, $path, $validateFields);
            $property = $response['result']['data']['premium_sum'];
            $policyIdProperty = $response['result']['data']['isn'];
            $policyNumberProperty = $response['result']['data']['policy_no'];
        }
        $options = $contract->options ?? [];
        $options['price'] = [
            'priceLife' => $life,
            'priceProperty' => $property,
        ];
        $options['isn'] = [
            'isnLife' => isset($policyIdLife) ? $policyIdLife : null,
            'isnProperty' => isset($policyIdProperty) ? $policyIdProperty : null,
        ];
        $contract->options = $options;
        return new CreatedPolicy(
            null,
            isset($policyIdLife) ? $policyIdLife : null,
            isset($policyIdProperty) ? $policyIdProperty : null,
            $life ?? null,
            $property ?? null,
            $policyNumberLife ?? null,
            $policyNumberProperty ?? null,
        );
    }

    /**
     * @inheritDoc
     */

    public function generatePDF($bytes, $filename): string
    {
        $filepath = Storage::path($this->pdfpath);
        if (!Storage::exists($this->pdfpath)) {
            if (!mkdir($filepath, 0777, true) && !is_dir($filepath)) {
                throw new RuntimeException('Directory "%s" was not created :' . $filepath);
            }
        }
        $pdf = base64_decode($bytes);
        Storage::put($this->getFileName($filename), $pdf);
        $base64 = $this->generateBase64($this->getFileName($filename));
        return $base64;
    }

    public function policyExist($isn): bool
    {
        $result = Storage::exists($this->getFileName($isn));
        return $result;
    }

    public function getPolicyIsn($contract): array
    {
        switch ($contract->getOptionsAttribute()['programCode']) {
            case self::IS_PROPERTY_AND_LIFE:
                $response = [
                    $contract->getOptionsAttribute()['isn']['isnLife'],
                    $contract->getOptionsAttribute()['isn']['isnProperty'],
                ];
                break;
            case self::IS_PROPERTY:
                $response = [
                    $contract->getOptionsAttribute()['isn']['isnProperty'],
                ];

                break;
            case self::IS_LIFE:
                $response = [
                    $contract->getOptionsAttribute()['isn']['isnLife'],
                ];
                break;
        }
        return $response;
    }

    public static function generateBase64($path): string
    {
        return base64_encode(Storage::get($path));
    }

    public function getFileName($filename)
    {
        $filename = $this->pdfpath . $filename . '.pdf';
        return $filename;
    }

    public function printPolicy(Contract $contract, bool $sample, bool $reset, ?string $filePath = null)
    {
        $isnArray = $this->getPolicyIsn($contract);
        if (empty($isnArray)) {
            throw new RuntimeException('ISN not found for this contract');
        }
            foreach ($isnArray as $isn) {
                if ($this->policyExist($isn)) {
                    $result[] = [
                        $this->generateBase64($this->getFileName($isn)),
                    ];
                } else {
                    $validateFields = [
                        'result' => 'required',
                        'results.*.data' => 'required',
                        'results.*.data.*.document' => 'required',
                        'results.*.data.*.document.*.bytes' => 'required',
                    ];
                    $bytes = $this->get(self::PRINT_POLICY_PATH.$isn,
                        $validateFields)['result']['data']['document']['bytes'];
                    $result[] = [
                        $this->generatePDF($bytes, $isn),
                    ];
                }
            }
            return $result;
    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contract $contract): void
    {
        $validateFields = [
            'status' => 'required',
            'results' => 'required',
        ];
        $isnArray = $this->getPolicyIsn($contract);
        foreach ($isnArray as $isn) {
            $response = $this->put(self::RELEASED_POLICY_PATH.$isn, $validateFields);
        }
    }

    /**
     * @inheritDoc
     */
    public function sendPolice(Contract $contract): string
    {
        // TODO: Implement sendPolice() method.
    }
}
