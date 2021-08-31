<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLink;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Traits\DriverTrait;
use App\Exceptions\Drivers\AbsoluteDriverException;
use App\Exceptions\Drivers\AbsoluteDriverValidationException;
use App\Exceptions\Drivers\DriverException;
use App\Models\Contract;
use App\Services\PayService\PayLinks;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class AbsoluteDriver
 *
 * @package App\Drivers
 */
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
    protected string $calculateLifePath;
    protected string $calculatePropertyPath;
    protected string $lifeAgreementPath;
    protected string $propertyAgreementPath;
    protected string $printPolicyPath;
    protected string $releasedPolicyPath;
    protected const ADDRESS_CODE_OBJECT = 2247;
    protected const ADDRESS_CODE_SUBJECT = 2246;
    protected const CONTACT_CODE_EMAIL = 2243;
    protected const CONTACT_CODE_PHONE = 2240;
    protected const DOCUMENT_CODE_PASSPORT = 1165;
    protected const LIFE_OBJECT = 'life';
    protected const PROPERTY_OBJECT = 'property';

    /**
     * AbsoluteDriver constructor.
     *
     * @param  Repository  $repository
     * @param  string      $prefix
     */
    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->baseUrl = $repository->get($prefix . 'base_Url');
        $this->ClientID = $repository->get($prefix . 'client_id');
        $this->ClientSecret = $repository->get($prefix . 'client_secret');
        $this->pdfpath = $repository->get($prefix . 'pdf.path');
        $this->grantType = $repository->get($prefix . 'grant_type');
        $this->calculateLifePath = $repository->get($prefix . 'calculate_life_path');
        $this->calculatePropertyPath = $repository->get($prefix . 'calculate_property_path');
        $this->lifeAgreementPath = $repository->get($prefix . 'life_agreement_path');
        $this->propertyAgreementPath = $repository->get($prefix . 'property_agreement_path');
        $this->printPolicyPath = $repository->get($prefix . 'print_policy_path');
        $this->releasedPolicyPath = $repository->get($prefix . 'released_policy_path');

        $this->client = App::make(Client::class);
        $this->paymentService = App::make(
            PaymentService::class,
            ['host' => $repository->get($prefix . 'pay_host')]
        );
    }

    /**
     * @return string
     * @throws AbsoluteDriverException
     */
    protected function getAccessToken(): string
    {
        if (empty($this->accessToken)) {
            $this->accessToken = $this->getToken();
        }

        return $this->accessToken;
    }

    /**
     * @return string
     * @throws AbsoluteDriverException
     */
    protected function getToken(): string
    {
        $data = ['grant_type' => $this->grantType];
        try {
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
        } catch (Throwable $throwable) {
            throw new AbsoluteDriverException(
                __METHOD__, 'Ошибка получения токена', AbsoluteDriverException::DEFAULT_CODE, $throwable
            );
        }

        if (!$response->getStatusCode() == 200) {
            throw new AbsoluteDriverException(__METHOD__, 'Ошибка получения токена');
        }

        $decodeResponse = json_decode($response->getBody()->getContents(), true);

        if (!Arr::has($decodeResponse, 'access_token')) {
            throw new AbsoluteDriverException(__METHOD__, 'В ответе нет access_token');
        }

        return $decodeResponse['access_token'];
    }

    /**
     * @param  ResponseInterface  $response
     * @param  array              $validateFields
     *
     * @return mixed
     * @throws AbsoluteDriverValidationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function decodeResponse(ResponseInterface $response, array $validateFields)
    {
        $decodeResponse = json_decode($response->getBody()->getContents(), true);
        $validator = Validator::make($decodeResponse, $validateFields);

        if (!$validator->validated()) {
            throw AbsoluteDriverValidationException::withMessages(
                __METHOD__,
                $validator->errors()->messages()
            );
        }

        return $decodeResponse;
    }

    /**
     * @param  array   $data
     * @param  string  $path
     * @param  array   $validateFields
     *
     * @return mixed
     * @throws AbsoluteDriverException
     * @throws AbsoluteDriverValidationException
     */
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
            throw new AbsoluteDriverException(
                __METHOD__,
                "POST запрос исключение от {$path} {$e->getMessage()}",
                DriverException::DEFAULT_CODE,
                $e
            );
        }
    }

    /**
     * @param  string  $path
     * @param  array   $validateFields
     *
     * @return mixed
     * @throws AbsoluteDriverException
     * @throws AbsoluteDriverValidationException
     */
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
            throw new AbsoluteDriverException(
                __METHOD__,
                "PUT запрос исключение от {$path} {$e->getMessage()}",
                DriverException::DEFAULT_CODE,
                $e
            );
        }
    }

    /**
     * @param  string  $path
     * @param  array   $validateFields
     *
     * @return mixed
     * @throws AbsoluteDriverException
     * @throws AbsoluteDriverValidationException
     * @throws \Illuminate\Validation\ValidationException
     */
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
            throw new AbsoluteDriverException(
                __METHOD__,
                "GET запрос исключение от {$path} {$e->getMessage()}",
                DriverException::DEFAULT_CODE,
                $e
            );
        }
    }

    /**
     * @param  array  $data
     *
     * @return bool
     */
    public static function isLife(array $data): bool
    {
        return Arr::exists($data, 'objects.life');
    }

    /**
     * @param  array  $data
     *
     * @return bool
     */
    public static function isProperty(array $data): bool
    {
        return Arr::exists($data, 'objects.property');
    }

    /**
     * @param  array  $data
     *
     * @return bool
     */
    public static function isPropertyAndLife(array $data): bool
    {
        return self::isLife($data) && self::isProperty($data);
    }

    /**
     * @param  array  $data
     *
     * @return string
     */
    public static function getGender(array $data): string
    {
        return Arr::get($data, 'objects.life.gender') == 0 ? 'М' : 'Ж';
    }

    /**
     * @param  array  $data
     *
     * @return CalculatedInterface
     * @throws AbsoluteDriverException
     * @throws AbsoluteDriverValidationException
     */
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
            $resultQuery = $this->post($body, $this->calculateLifePath, $validateFileds);
            $life = Arr::get($resultQuery, 'result.data.premium_sum');
        }
        if (self::isProperty($data) || self::isPropertyAndLife($data)) {
            $body = [
                'limit_sum' => Arr::get($data, 'remainingDebt'),
            ];
            $resultQuery = $this->post($body, $this->calculatePropertyPath, $validateFileds);
            $property = Arr::get($resultQuery, 'result.data.premium_sum');
        }
        $result = [
            'life' => $life,
            'property' => $property,
        ];

        return new Calculated(
            $data['isn'] ?? null, $result['life'] ?? null, $result['property'] ?? null
        );
    }

    /**
     * @param  Contract  $contract
     *
     * @return array
     */
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

    /**
     * @param  array  $data
     *
     * @return string
     */
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

    /**
     * @param  Contract  $contract
     * @param  array     $data
     *
     * @return CreatedPolicyInterface
     * @throws AbsoluteDriverException
     * @throws AbsoluteDriverValidationException
     */
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
                        'code' => self::ADDRESS_CODE_OBJECT,
                        'code_desc' => '',
                        'text' => self::getSubjectAddress($data),
                        'fias_id' => '',
                    ],
                    [
                        'code' => self::ADDRESS_CODE_SUBJECT,
                        'code_desc' => '',
                        'text' => self::getSubjectAddress($data),
                    ],
                ],
                'contact' => [
                    [
                        'code' => self::CONTACT_CODE_EMAIL,
                        'code_desc' => 'E-mail',
                        'text' => Arr::get($data, 'subject.email'),
                    ],
                    [
                        'code' => self::CONTACT_CODE_PHONE,
                        'text' => Arr::get($data, 'subject.phone'),
                    ],
                ],
                'document' => [
                    'code' => self::DOCUMENT_CODE_PASSPORT,
                    'series' => Arr::get($data, 'subject.docSeries'),
                    'number' => Arr::get($data, 'subject.docNumber'),
                    'issue_date' => Arr::get($data, 'subject.docIssueDate'),
                    'issue_by' => Arr::get($data, 'subject.docIssuePlace'),
                ],
            ],
        ];
        if (self::isLife($data)) {
            $path = $this->lifeAgreementPath;

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
                    'code' => self::ADDRESS_CODE_OBJECT,
                    'code_desc' => '',
                    'text' => self::getObjectPropertyAddress($data),
                    'fias_id' => '',
                ],
            ];

            $path = $this->propertyAgreementPath;
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
        $pdf = base64_decode($bytes);
        Storage::put($this->getFileName($filename), $pdf);

        return self::generateBase64($this->getFileName($filename));
    }

    /**
     * @param  string  $isn
     *
     * @return bool
     */
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

    /**
     * @param  string  $path
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function generateBase64(string $path): string
    {
        return base64_encode(Storage::get($path));
    }

    /**
     * @param  string  $filename
     *
     * @return string
     */
    public function getFileName(string $filename): string
    {
        return $this->pdfpath . $filename . '.pdf';
    }

    /**
     * @param  Contract     $contract
     * @param  bool         $sample
     * @param  bool         $reset
     * @param  string|null  $filePath
     *
     * @return array
     * @throws AbsoluteDriverException
     * @throws AbsoluteDriverValidationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function printPolicy(
        Contract $contract,
        bool $sample,
        bool $reset,
        ?string $filePath = null
    ) {
        $isnArray = $this->getPolicyIsn($contract);
        if (empty($isnArray)) {
            throw new AbsoluteDriverException('ISN для этого контракта не найден');
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
                    $this->printPolicyPath . $isn,
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
            $this->put($this->releasedPolicyPath . $isn, $validateFields);
        }
    }
}
