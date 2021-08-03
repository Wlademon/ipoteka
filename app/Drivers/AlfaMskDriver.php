<?php

namespace App\Drivers;

use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLink;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Services\MerchantServices;
use App\Drivers\Source\Alpha\AlfaAuth;
use App\Drivers\Source\Alpha\AlphaCalculator;
use App\Drivers\Traits\DriverTrait;
use App\Drivers\Traits\PrintPdfTrait;
use App\Exceptions\Drivers\AlphaException;
use App\Exceptions\Drivers\ReninsException;
use App\Models\Contract;
use App\Services\PayService\PayLinks;
use Carbon\Carbon;
use File;
use GuzzleHttp\Client;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Class AbsolutDriver
 *
 * @package App\Drivers
 */
class AlfaMskDriver implements DriverInterface
{
    use DriverTrait {
        DriverTrait::getStatus as getTStatus;
    }
    use PrintPdfTrait;

    const POST_POLICY_URL = '/mortgage/partner/calc';
    const POST_POLICY_CREATE_URL = '/mortgage/partner/calcAndSave';
    const GET_POLICY_STATUS_URL = '/mortgage/partner/contractStatus';
    const POST_PAYMENT_RECEIPT = '/payment/receipt/common';
    protected string $host;
    protected Client $client;
    protected int $managerId = 0;
    protected AlfaAuth $auth;
    protected int $numberIterations = 5;
    protected MerchantServices $merchantServices;

    /**
     * AlfaMskDriver constructor.
     *
     * @param  Repository  $repository
     * @param  string      $prefix
     *
     * @throws Throwable
     */
    public function __construct(Repository $repository, string $prefix = '')
    {
        $this->client = new Client();
        if (!$repository->get($prefix . 'host', false)) {
            throw new AlphaException('Not set host property');
        }
        if (
        !($repository->get($prefix . 'auth.username') && $repository->get($prefix . 'auth.pass') &&
          $repository->get($prefix . 'auth.auth_url'))
        ) {
            throw new AlphaException('Not set auth data');
        }
        $this->numberIterations = $repository->get($prefix . 'numberIterations', 5);
        $this->auth = new AlfaAuth(
            $repository->get($prefix . 'auth.username'),
            $repository->get($prefix . 'auth.pass'),
            $repository->get($prefix . 'auth.auth_url')
        );
        $this->host = $repository->get($prefix . 'host');
        $this->merchantServices = new MerchantServices($repository->get($prefix . 'merchan_host'));
    }

    /**
     * @inheritDoc
     * @throws AlphaException
     */
    public function calculate(array $data): CalculatedInterface
    {
        $authToken = $this->auth->getToken($this->client)['access_token'];
        $calculator = $this->collectData($data);

        Log::info(
            __METHOD__ . ' расчет полиса',
            [
                'request' => $calculator->getData(),
            ]
        );
        $result = $this->client->post(
            $this->host . self::POST_POLICY_URL,
            [
                'headers' => [
                    'Authorization' => "Bearer {$authToken}",
                ],
                'json' => $calculator->getData(),
            ]
        );

        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error calc', Response::HTTP_NOT_ACCEPTABLE);
        }
        $response = $result->getBody()->getContents();
        Log::info(
            __METHOD__ . ' расчет окончен',
            [
                'response' => $response,
            ]
        );
        $decodeResult = json_decode($response, true);

        return new Calculated(
            $data['contractId'] ?? null,
            Arr::get($decodeResult, 'lifePremium', 0),
            Arr::get($decodeResult, 'propertyPremium', 0)
        );
    }

    /**
     * @param  array  $data
     *
     * @return AlphaCalculator
     * @throws Throwable
     */
    protected function collectData(array $data): AlphaCalculator
    {
        $dataCollect = collect($data);
        $calculator = new AlphaCalculator();
        $calculator->setBank(
            $dataCollect->get('mortgageeBank'),
            $dataCollect->get('remainingDebt')
        );
        $calculator->setCalcDate($dataCollect->get('activeFrom'));

        $objects = collect($dataCollect->get('objects'));

        if ($objects->has('life')) {
            $life = $objects->get('life');

            $calculator->setInsurant(
                Arr::get($life, 'gender'),
                Arr::get($life, 'birthDate')
            );
            $calculator->setLifeRisk(
                Arr::get($life, 'professions', []),
                Arr::get($life, 'sports', [])
            );
        }
        if ($objects->has('property')) {
            $property = $objects->get('property');
            $calculator->setInsurance();
            $calculator->setPropertyRisk(
                'Москва',
                Arr::get($property, 'isWooden', false),
                Arr::get($property, 'buildYear')
            );
        }

        return $calculator;
    }

    /**
     * @param $authToken
     * @param $contractList
     * @return array
     * @throws Throwable
     */
    protected function createSingleAccount($authToken, $contractList): array
    {
        try {
            $data = [
                'contractList' => $contractList,
            ];
            Log::info(
                __METHOD__ . ' Создание единого аккаунта',
                [
                    'url' => $this->host . self::POST_PAYMENT_RECEIPT,
                    'request' => $data,
                ]
            );
            $result = $this->client->post(
                $this->host . self::POST_PAYMENT_RECEIPT,
                [
                    'headers' => [
                        'Authorization' => "Bearer {$authToken}",
                    ],
                    'json' => $data,
                ]
            );
        } catch (Throwable $e) {
            Log::error(__METHOD__ . ' Ошибка при создании единого аккаунта', [
                'headers' => [
                    'Authorization' => "Bearer {$authToken}",
                ],
                'json' => $data,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'class' => get_class($e)
            ]);
            throw new AlphaException($e->getMessage());
        }

        throw_if($result->getStatusCode() !== 200, new AlphaException('Error create payment'));
        $decodeResult = json_decode($result->getBody()->getContents(), true);
        Log::info(
            __METHOD__ . ' Единый аккаунт создан',
            [
                'response' => $decodeResult,
            ]
        );

        return [
            Arr::get($decodeResult, 'id', 0),
            Arr::get($decodeResult, 'number', 0),
        ];
    }

    /**
     * @return string
     */
    protected function getIsOperDocument(): string
    {
        return 'y';
    }

    /**
     * @param  Contract  $contract
     * @param  PayLinks   $payLinks
     *
     * @return PayLinkInterface
     * @throws AlphaException
     * @throws Throwable
     */
    public function getPayLink(Contract $contract, PayLinks $payLinks): PayLinkInterface
    {
        $contractOptions = $contract->getOptionsAttribute();
        $contractOptions['singleAccount'] = '';
        $contractOptions['orderId'] = '';

        $authToken = $this->auth->getToken($this->client)['access_token'];

        $registerOrder = $this->merchantServices;
        $singleAcc = $this->createSingleAccount(
            $authToken,
            $contract->getOptionsAttribute()['contractList']
        );
        $contractOptions['singleAccount'] = $singleAcc[0];
        $registerOrder->setDescription($singleAcc[0]);
        $registerOrder->setMerchantOrderNumber($singleAcc[0]);

        $registerOrder->setExpirationDate(
            Carbon::now()->addMinutes(20)->format('Y-m-d\TH:i:sP')
        );
        $registerOrder->setIsOperDocument($this->getIsOperDocument());
        $registerOrder->setReturnUrl(url($payLinks->getSuccessUrl()));
        $registerOrder->setFailUrl(url($payLinks->getFailUrl()));

        Log::info(
            __METHOD__ . ' Получение ссылки на оплату',
            [
                'request' => $registerOrder->getData(),
            ]
        );
        $response = $registerOrder->registerOrder();

        throw_if(empty($response->get('orderId')), new AlphaException('Missing orderId'));
        Log::info(
            __METHOD__ . ' Ссылка на оплату получена',
            [
                'response' => $response->all(),
            ]
        );
        $contractOptions['orderId'] = $response->get('orderId');
        $contract->setOptionsAttribute($contractOptions);
        $contract->save();

        return new PayLink(
            $response->get('orderId'), $response->get('formUrl'), $contract->remainingDebt
        );
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface
    {
        $contractOptions = $contract->getOptionsAttribute();
        $contractOptions['contractList'] = [];
        $authToken = $this->auth->getToken($this->client)['access_token'];

        $dataCollect = collect($data);
        $policy = $this->collectData($data);

        $subject = collect($dataCollect->get('subject'));
        $property = collect(Arr::get($data, 'objects.property'));

        $policy->setInsurerAddress($subject->get('city'), $subject->get('street'));
        $policy->setInsurerEmail($subject->get('email'));
        $policy->setInsurerFullName(
            $subject->get('firstName'),
            $subject->get('lastName'),
            $subject->get('middleName')
        );
        $policy->setInsurerPersonDocument(
            (new Carbon($subject->get('docIssueDate')))->format('Y-m-d'),
            $subject->get('docNumber'),
            $subject->get('docSeries')
        );
        $policy->setInsurerPhone($subject->get('phone'));
        if ($property->isNotEmpty()) {
            $policy->setAddress(
                $property->only(['city', 'state', 'street', 'house', 'block', 'apartment'])->join(
                    ','
                )
            );
            $policy->setAddressSquare($property->get('area'));
        }
        $policy->setDateCreditDoc((new Carbon())->format('Y-m-d'));
        $policy->setNumberCreditDoc($dataCollect->get('mortgageAgreementNumber')); //?
        Log::info(
            __METHOD__ . ' Создание полиса',
            [
                'request' => $policy->getData(),
            ]
        );
        $decodePostResult = $this->getDataFromCreatePolicy($authToken, $policy);
        Log::info(
            __METHOD__ . ' Полис создан',
            [
                'response' => $decodePostResult,
            ]
        );
        $contractOptions['upid'] = Arr::get($decodePostResult, 'upid');
        $decodeGetResult = $this->getStatusContract(
            $authToken,
            Arr::get($decodePostResult, 'upid'),
            'Error get data from createPolicy'
        );

        if (Arr::has($decodeGetResult['propertyContract'], 'contractId')) {
            $contractOptions['contractList'][] = Arr::get(
                $decodeGetResult['propertyContract'],
                'contractId',
                0
            );
        }
        if (Arr::has($decodeGetResult['lifeContract'], 'contractId')) {
            $contractOptions['contractList'][] = Arr::get(
                $decodeGetResult['lifeContract'],
                'contractId',
                0
            );
        }
        $contract->setOptionsAttribute($contractOptions);
        $contract->save();

        return new CreatedPolicy(
            $contract->id,
            Arr::get($decodeGetResult['lifeContract'], 'contractId', 0),
            Arr::get($decodeGetResult['propertyContract'], 'contractId', 0),
            Arr::get($decodeGetResult, 'lifePremium', 0),
            Arr::get($decodeGetResult, 'propertyPremium', 0),
            null,
            null
        );
    }

    /**
     * @param $authToken
     * @param $policy
     *
     * @return array
     * @throws AlphaException
     */
    protected function getDataFromCreatePolicy(string $authToken, AlphaCalculator $policy): array
    {
        try {
            $postResult = $this->client->post(
                $this->host . self::POST_POLICY_CREATE_URL,
                [
                    'headers' => [
                        'Authorization' => "Bearer {$authToken}",
                    ],
                    'json' => $policy->getData(),
                ]
            );
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage(), 0, $e);
        }

        if ($postResult->getStatusCode() !== 200) {
            throw new AlphaException('Error create policy');
        }
        $decodePostResult = json_decode($postResult->getBody()->getContents(), true);

        if (!Arr::has($decodePostResult, 'upid')) {
            throw new AlphaException('Response has not upid');
        }

        return $decodePostResult;
    }

    /**
     * @param  Contract  $contract
     *
     * @return array
     * @throws ReninsException
     */
    protected function getFilePolice(Contract $contract): array
    {
        $objects = $contract->objects;
        $files = [];
        foreach ($objects as $object) {
            $filePathObject = self::createFilePath($contract, $object->id);
            if (!$this->isFilePoliceExitst($contract, $filePathObject)) {
                $this->printPolicy($contract, false, true);
            }
            $files[] = public_path($filePathObject);
        }

        return $files;
    }

    /**
     * @param $authToken
     * @param $upid
     * @param $message
     *
     * @return mixed
     * @throws AlphaException
     */
    protected function getStatusContract($authToken, $upid, $message)
    {
        sleep(5);
        $i = 0;
        Log::info(
            __METHOD__ . ' Получение статуса сделки',
            [
                'upid' => $upid,
            ]
        );
        do {
            $i++;
            try {
                $getResult = $this->client->get(
                    $this->host . self::GET_POLICY_STATUS_URL,
                    [
                        'headers' => [
                            'Authorization' => "Bearer {$authToken}",
                        ],
                        'query' => [
                            'upid' => $upid,
                        ],
                    ]
                );
                if ($getResult->getStatusCode() !== 200) {
                    throw new AlphaException($message);
                }
            } catch (Throwable $e) {
                throw new AlphaException($e->getMessage(), 0, $e);
            }
            $response = json_decode($getResult->getBody()->getContents(), true);
            $contracts = Arr::only($response, ['lifeContract', 'propertyContract']);
            $contractIds = array_filter(Arr::pluck($contracts, 'contractId'));
            usleep(500000);
        } while ((count($contracts) !== count($contractIds)) ||
                 ($i < ((int)$this->numberIterations)));

        if (count($contracts) !== count($contractIds)) {
            throw new AlphaException('Misstake contractId');
        }
        Log::info(
            __METHOD__ . ' Получен статус сделки',
            [
                'response' => $response,
            ]
        );

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function printPolicy(
        Contract $contract,
        bool $sample,
        bool $reset,
        ?string $filePath = null
    ) {
        $files = [];
        $merchantService = $this->merchantServices;
        $objects = $contract->objects;
        $objectIds = $objects->pluck('integration_id', 'id');
        foreach ($objectIds as $id => $extId) {
            $filePath = self::createFilePath($contract, $id);
            if ($this->isFilePoliceExitst($contract, $filePath)) {
                $files[] = self::generateBase64($filePath);
                $objectIds->forget($id);
            }
        }
        if ($objectIds->count()) {
            $response = $merchantService->getContractSigned(
                $contract->getOptionsAttribute()['upid'],
                $objectIds->values()->all()
            );
            if ($response) {
                foreach ($response as $extId => $item) {
                    $filePath = self::createFilePath($contract, $objectIds->flip()->get($extId));
                    File::move(storage_path('app/' . $item), public_path($filePath));
                    $files[] = self::generateBase64($filePath);
                }
            }
        }

        return $files;
    }

    /**
     * @param  Contract  $contract
     *
     * @return array
     * @throws Throwable
     */
    public function getStatus(Contract $contract): array
    {
        if (!empty($contract->getOptionsAttribute()['orderId'])) {
            if ($contract->status !== Contract::STATUS_CONFIRMED) {
                try {
                    $clientStatusOrder = $this->merchantServices;
                    $statusOrder = $clientStatusOrder->getOrderStatus(
                        $contract->getOptionsAttribute()['orderId']
                    );
                } catch (Throwable $throwable) {
                    Log::error($throwable->getMessage());
                }

                if ($statusOrder->get('orderStatus') === Contract::STATUS_CONFIRMED) {
                    $contract->status = Contract::STATUS_CONFIRMED;
                    $contract->saveOrFail();
                }
            }
        }

        return $this->getTStatus($contract);
    }

    /**
     * @param  Contract  $contract
     */
    public function payAccept(Contract $contract): void
    {
        $this->getStatus($contract);

        if ($contract->status != Contract::STATUS_CONFIRMED) {
            throw new AlphaException(
                'Платеж не выполнен.', HttpResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
