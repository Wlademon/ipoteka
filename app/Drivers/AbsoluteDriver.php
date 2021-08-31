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
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class AbsoluteDriver implements DriverInterface, LocalPaymentDriverInterface
{
    use DriverTrait;

    /**
     * @inheritDoc
     */
    protected string $baseUrl;
    protected ?string $accessToken = null;
    protected string $ClientID;
    protected string $ClientSecret;
    protected PaymentService $paymentService;
    protected string $pdfpath;
    protected string $grantType;
    protected Client $client;
    protected const CALCULATE_LIFE_PATH = '/api/mortgage/sber/life/calculation/create';
    protected const CALCULATE_PROPERTY_PATH = '/api/mortgage/sber/property/calculation/create';
    protected const LIFE_AGREEMENT_PATH = '/api/mortgage/sber/life/agreement/create';
    protected const PROPERTY_AGREEMENT_PATH = '/api/mortgage/sber/property/agreement/create';
    protected const PRINT_POLICY_PATH = '/api/print/agreement/';
    protected const RELEASED_POLICY_PATH = '/api/agreement/set/released/';
    protected const ADDRESS_CODE_2247 = 2247;
    protected const ADDRESS_CODE_2246 = 2246;
    protected const CONTACT_CODE_2243 = 2243;
    protected const CONTACT_CODE_2240 = 2240;
    protected const DOCUMENT_CODE_1165 = 1165;
    protected const LIFE_OBJECT = 'life';
    protected const PROPERTY_OBJECT = 'property';

    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->baseUrl = $repository->get($prefix . 'base_Url');
        $this->ClientID = $repository->get($prefix . 'client_id');
        $this->ClientSecret = $repository->get($prefix . 'client_secret');
        $this->pdfpath = $repository->get($prefix . 'pdf.path');
        $this->grantType = $repository->get($prefix . 'grant_type');

        $this->client = new Client();
        $this->paymentService = new PaymentService($repository->get($prefix . 'pay_host'));
    }

    protected function getAccessToken(): string
    {
        if (empty($this->accessToken)) {
            $this->accessToken = $this->getToken();
        }

        return $this->accessToken;
    }

    protected function getToken(): string
    {
        $data = ['grant_type' => $this->grantType];
        $response = $this->client->request(
            'POST',
            $this->baseUrl . '/oauth/token',
            [
                'auth' => [
                    $this->ClientID,
                    $this->ClientSecret,
                ],
                'form_params' => $data,
            ]
        );

        if (!$response->getStatusCode() == 200) {
            throw new Exception('Error get token');
        }

        $decodeResponse = json_decode($response->getBody()->getContents(), true);

        if (!Arr::has($decodeResponse, 'access_token')) {
            throw new Exception('Response has not access_token');
        }

        return $decodeResponse['access_token'];
    }

    public static function decodeResponse(ResponseInterface $response, array $validateFields)
    {
        $decodeResponse = json_decode($response->getBody()->getContents(), true);
        $validator = Validator::make($decodeResponse, $validateFields);

        if (!$validator->validated()) {
            throw ValidationException::withMessages($validator->errors()->messages());
        }

        return $decodeResponse;
    }

    public function post(array $data, string $path, array $validateFields)
    {
        try {
            $response = $this->client->post(
                $this->baseUrl . $path,
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->getAccessToken()}",
                    ],
                    'json' => $data,
                ]
            );

            return self::decodeResponse($response, $validateFields);
        } catch (GuzzleException $e) {
            throw new RuntimeException("POST request exception from {$path} {$e->getMessage()}");
        }
    }

    public function put(string $path, array $validateFields)
    {
        try {
            $response = $this->client->put(
                $this->baseUrl . $path,
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->getAccessToken()}",
                    ],
                ]
            );

            return self::decodeResponse($response, $validateFields);
        } catch (GuzzleException $e) {
            throw new RuntimeException("PUT request exception from {$path} {$e->getMessage()}");
        }
    }

    public function get(string $path, array $validateFields)
    {
        try {
            $response = $this->client->get(
                $this->baseUrl . $path,
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->getAccessToken()}",
                    ],
                ]
            );

            return self::decodeResponse($response, $validateFields);
        } catch (GuzzleException $e) {
            throw new RuntimeException("Put request exception from {$path} {$e->getMessage()}");
        }
    }

    public static function isLife(array $data): bool
    {
        return Arr::exists($data, 'objects.life');
    }

    public static function isProperty(array $data): bool
    {
        return Arr::exists($data, 'objects.property');
    }

    public static function isPropertyAndLife(array $data): bool
    {
        return self::isLife($data) && self::isProperty($data);
    }

    public static function getGender(array $data): string
    {
        return Arr::get($data, 'objects.life.gender') == 0 ? 'М' : 'Ж';
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
        if (self::isLife($data) || self::isPropertyAndLife($data)) {
            $body = [
                'limit_sum' => Arr::get($data, 'remainingDebt'),
                'sex' => self::getGender($data),
                'birthday' => Arr::get($data, 'objects.life.birthDate'),
            ];
            $resultQuery = $this->post($body, self::CALCULATE_LIFE_PATH, $validateFileds);
            $life = Arr::get($resultQuery, 'result.data.premium_sum');
        }
        if (self::isProperty($data) || self::isPropertyAndLife($data)) {
            $body = [
                'limit_sum' => Arr::get($data, 'remainingDebt'),
            ];
            $resultQuery = $this->post($body, self::CALCULATE_PROPERTY_PATH, $validateFileds);
            $property = Arr::get($resultQuery, 'result.data.premium_sum');
        }
        $result = [
            'life' => $life,
            'property' => $property,
        ];

        return new Calculated(
            $data['isn'] ?? null,
            $result['life'] ?? null,
            $result['property'] ?? null
        );
    }

    protected function getProducts(Contract $contract): array
    {
        return $contract->objects->pluck('product')->all();
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

        $products = $this->getProducts($contract);
        $array = [];
        if (in_array(self::LIFE_OBJECT, $products)) {
            $array[] = [
                'price' => Arr::get($contract->options, 'price.priceLife'),
                'isn' => Arr::get($contract->options, 'isn.isnLife'),
                'description' => 'Жизнь',
            ];
        }
        if (in_array(self::PROPERTY_OBJECT, $products)) {
            $array[] = [
                'price' => Arr::get($contract->options, 'price.priceProperty'),
                'isn' => Arr::get($contract->options, 'isn.isnProperty'),
                'description' => 'Имущество',
            ];
        }

        $result = $this->paymentService->payLink($contract, $urls, $array);

        return new PayLink(
            $result['orderId'], $result['url'], $contract->remainingDebt
        );
    }

    /**
     * @inheritDoc
     */

    public static function getSubjectAddress(array $data): string
    {
        $arr = [
            Arr::get($data, 'subject.state'),
            Arr::get($data, 'subject.city'),
            Arr::get($data, 'subject.street'),
            Arr::get($data, 'subject.house'),
            Arr::get($data, 'subject.block'),
            Arr::get($data, 'subject.apartment'),
        ];

        return implode(', ', array_filter($arr));
    }

    public static function getObjectPropertyAddress(array $data): string
    {
        $arr = [
            Arr::get($data, 'objects.property.state'),
            Arr::get($data, 'objects.property.city'),
            Arr::get($data, 'objects.property.street'),
            Arr::get($data, 'objects.property.house'),
            Arr::get($data, 'objects.property.block'),
            Arr::get($data, 'objects.property.apartment'),
        ];

        return implode(', ', array_filter($arr));
    }

    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface
    {
        $body = [
            'date_begin' => Arr::get($data, 'activeFrom'),
            'agr_credit_number' => Arr::get($data, 'mortgageAgreementNumber'),
            'agr_credit_date_conc' => Arr::get($data, 'activeTo'),
            'limit_sum' => Arr::get($data, 'remainingDebt'),
            'policy_holder' => [
                'lastname' => Arr::get($data, 'subject.lastName'),
                'firstname' => Arr::get($data, 'subject.firstName'),
                'parentname' => Arr::get($data, 'subject.middleName'),
                'sex' => Arr::get($data, 'subject.gender') == 0 ? 'М' : 'Ж',
                'birthday' => Arr::get($data, 'subject.birthDate'),
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
                        'text' => Arr::get($data, 'subject.email'),
                    ],
                    [
                        'code' => self::CONTACT_CODE_2240,
                        'text' => Arr::get($data, 'subject.phone'),
                    ],
                ],
                'document' => [
                    'code' => self::DOCUMENT_CODE_1165,
                    'series' => Arr::get($data, 'subject.docSeries'),
                    'number' => Arr::get($data, 'subject.docNumber'),
                    'issue_date' => Arr::get($data, 'subject.docIssueDate'),
                    'issue_by' => Arr::get($data, 'subject.docIssuePlace'),
                ],
            ],
        ];
        if (self::isLife($data)) {
            $path = self::LIFE_AGREEMENT_PATH;

            $validateFields = [
                'result' => 'required',
                'result.*.data' => 'required',
                'result.*.data.*.premium_sum' => 'required',
                'result.*.data.*.isn' => 'required',
                'result.*.data.*.policy_no' => 'required',
            ];

            $response = $this->post($body, $path, $validateFields);

            $responseData = Arr::get($response, 'result.data');
            $life = Arr::get($responseData, 'premium_sum');

            $policyIdLife = Arr::get($responseData, 'isn');
            $policyNumberLife = Arr::get($responseData, 'policy_no');
        }
        if (self::isProperty($data)) {
            $body['ins_object'] = [
                'address' => [
                    'code' => self::ADDRESS_CODE_2247,
                    'code_desc' => '',
                    'text' => self::getObjectPropertyAddress($data),
                    'fias_id' => '',
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

    public function generatePDF(string $bytes, string $filename): string
    {
        $filepath = Storage::path($this->pdfpath);
        if (!Storage::exists($this->pdfpath)) {
            if (!mkdir($filepath, 0777, true) && !is_dir($filepath)) {
                throw new RuntimeException('Directory "%s" was not created :' . $filepath);
            }
        }
        $pdf = base64_decode($bytes);
        Storage::put($this->getFileName($filename), $pdf);

        return self::generateBase64($this->getFileName($filename));
    }

    public function policyExist(string $isn): bool
    {
        return Storage::exists($this->getFileName($isn));
    }

    public function getPolicyIsn(Contract $contract): array
    {
        $products = $this->getProducts($contract);
        $response = [];
        if (in_array(self::PROPERTY_OBJECT, $products)) {
            $response[] = $contract->getOptionsAttribute()['isn']['isnProperty'];
        }
        if (in_array(self::LIFE_OBJECT, $products)) {
            $response[] = $contract->getOptionsAttribute()['isn']['isnLife'];
        }

        return $response;
    }

    public static function generateBase64(string $path): string
    {
        return base64_encode(Storage::get($path));
    }

    public function getFileName(string $filename): string
    {
        return $this->pdfpath . $filename . '.pdf';
    }

    public function printPolicy(
        Contract $contract,
        bool $sample,
        bool $reset,
        ?string $filePath = null
    ) {
        $isnArray = $this->getPolicyIsn($contract);
        if (empty($isnArray)) {
            throw new RuntimeException('ISN not found for this contract');
        }
        foreach ($isnArray as $isn) {
            if ($this->policyExist($isn)) {
                $result[] = [
                    self::generateBase64($this->getFileName($isn)),
                ];
            } else {
                $validateFields = [
                    'result' => 'required',
                    'results.*.data' => 'required',
                    'results.*.data.*.document' => 'required',
                    'results.*.data.*.document.*.bytes' => 'required',
                ];
                $bytes = $this->get(
                    self::PRINT_POLICY_PATH . $isn,
                    $validateFields
                )['result']['data']['document']['bytes'];
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
            $this->put(self::RELEASED_POLICY_PATH . $isn, $validateFields);
        }
    }
}
