<?php

namespace App\Helpers;

use App\Exceptions\Services\PolicyServiceException;
use App\Models\BaseModel;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\SiteService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

/**
 * Class Helper
 *
 * @package App\Helpers
 */
class Helper
{
    /** @var BaseModel[] $models */
    private static array $models = [];

    /**
     * Получить список всех моделей
     *
     * @return array
     */
    public static function getAllModels(): array
    {
        if (count(self::$models) === 0) {
            self::addClassesFromPathsArray(glob(base_path() . '/app/Models/*.php'));
        }

        return self::$models;
    }

    /**
     * @param  array  $filePaths
     */
    private static function addClassesFromPathsArray(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            $modelName = self::getClassFromFilePath($filePath);
            if (
                $modelName !== '' && class_exists($modelName)
            ) {
                self::$models[] = $modelName;
            }
        }
    }

    /**
     * Получить имя класса по пути файла
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function getClassFromFilePath(string $path): string
    {
        $class = preg_replace('/.*app\\//', 'App/', $path);

        return str_replace(['/', '.php'], ['\\', ''], $class);
    }

    /**
     * @param $field
     *
     * @return mixed
     */
    public static function getLocaleAttr($field)
    {
        $locale = App::getLocale();
        if (isset($field[$locale])) {
            return $field[$locale];
        }
        if (isset ($field[config('app.fallback_locale')])) {
            return $field[config('app.fallback_locale')];
        }

        if (count($field)) {
            return array_shift($field);
        }

        return $field ?: null;
    }

    /**
     * Получить обновляемые поля модели
     *
     * @param  array  $currentModel
     * @param  array  $newModel
     *
     * @return array
     */
    public static function getUpdatesModelParams(array $currentModel, array $newModel): array
    {
        /** @var Model $currentModel */
        $updatedParams = [];
        foreach ($newModel as $param => $value) {
            if ($param === '_token' || $param === 'updated_at') {
                continue;
            }
            if (
                is_array($value) && isset($currentModel[$param]) && is_array($currentModel[$param])
            ) {
                $innerParams = self::getUpdatesModelParams($currentModel[$param], $value);
                if (count($innerParams) > 0) {
                    $updatedParams[$param] = $value;
                    continue;
                }
            }
            if ($param === 'password' || !is_string($param)) {
                $updatedParams[$param] = $value;
                continue;
            }
            if (!isset($currentModel[$param]) || $value !== $currentModel[$param]) {
                $updatedParams[$param] = $value;
            }
        }

        return $updatedParams;
    }

    /**
     * @param $params
     *
     * @return mixed
     * @throws PolicyServiceException
     * @throws \JsonException
     */
    public static function getPolicyNumber($params)
    {
        if (in_array(config('app.env'), ['local', 'testing'])) {
            return json_decode(
                json_encode(
                    ['data' => ['bso_numbers' => ['Z6921/397/RU0000/20']]],
                    JSON_THROW_ON_ERROR
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }
        $client = new Client();
        $url = config('services.bishop.host') . '/bso';

        Log::info(__METHOD__ . '. Params - ', [$url, $params]);
        $httpStatusCode = Response::HTTP_BAD_REQUEST;

        try {
            $response = $client->post(
                $url,
                [
                    'body' => json_encode($params, JSON_THROW_ON_ERROR),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            $json = [];
            if (($e instanceof RequestException) && $e->getResponse() !== null) {
                $json = json_decode($e->getResponse()->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $httpStatusCode = $e->getResponse()->getStatusCode();
            }
            Log::error(
                __METHOD__ . '. Exception:',
                [$json, $e->getMessage(), $e->getTraceAsString()]
            );

            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        } catch (Exception $e) {
            Log::error(
                __METHOD__ . '. ERROR - Response: ',
                [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]
            );

            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);

            throw new PolicyServiceException(
                'ERROR - Response: ' . json_encode($response->getBody(), JSON_THROW_ON_ERROR),
                $response->getStatusCode()
            );
        }
        Log::info(
            __METHOD__ . '. Response',
            [
                json_decode(
                    $response->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
            ]
        );

        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Подтвердить номер полиса
     *
     * @param $params
     *
     * @return mixed|void
     * @throws PolicyServiceException|\JsonException
     */
    public static function acceptPolicyNumber($params)
    {
        if (in_array(config('app.env'), ['local', 'testing'])) {
            return true;
        }
        $client = new Client();
        $url = config('services.bishop.host') . '/bso/accept';

        Log::info(__METHOD__ . '. Params - ', [$url, $params]);
        $httpStatusCode = Response::HTTP_BAD_REQUEST;

        try {
            $response = $client->post(
                $url,
                [
                    'body' => json_encode($params, JSON_THROW_ON_ERROR),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            $json = [];
            if (($e instanceof RequestException) && $e->getResponse() !== null) {
                $json = json_decode(
                    $e->getResponse()->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
                $httpStatusCode = $e->getResponse()->getStatusCode();
            }
            Log::error(
                __METHOD__ . '. Exception:',
                [$json, $e->getMessage(), $e->getTraceAsString()]
            );
            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        } catch (Exception $e) {
            Log::error(
                __METHOD__ . '. ERROR - Response: ',
                [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]
            );
            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);
            throw new PolicyServiceException(
                'ERROR - Response: ' . json_encode($response->getBody(), JSON_THROW_ON_ERROR),
                $response->getStatusCode()
            );
        }
        Log::info(
            __METHOD__ . '. Response',
            [
                json_decode(
                    $response->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
            ]
        );

        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Экспорт полиса в Uwin - вернет номер ContractId
     *
     * @param  Contract  $contract
     *
     * @return mixed|void
     * @throws \JsonException
     */
    public static function getUwinContractId(Contract $contract)
    {
        if (in_array(config('app.env'), ['local', 'testing'])) {
            return json_decode(
                json_encode(['contractId' => '111111'], JSON_THROW_ON_ERROR),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }
        $siteService = new SiteService();

        $subject = $contract->subject;
        if (!isset($subject->value['subjectId'])) {
            Log::info(__METHOD__ . '. getUserData subject', [$subject->value]);
            $user = $siteService->getUserData($subject->value);
            if ($user) {
                $uwUserData = [
                    'login' => Arr::get($user, 'login'),
                    'subjectId' => Arr::get($user, 'subjectId'),
                ];
                $subject->value = json_encode(
                    array_merge($subject->value, $uwUserData),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                );
                $subject->save();
            }
        }
        foreach ($contract->objects as $obj) {
            if (!isset($obj->value['subjectId'])) {
                Log::info(__METHOD__ . '. getUserData object', [$obj->value]);
                $code = md5(
                    $obj->value['lastName'] . $obj->value['firstName'] .
                    ($obj->value['middleName'] ?? '') . $obj->value['birthDate'] . time()
                );
                $obj->value = json_encode(
                    array_merge($obj->value, ['email' => $code . '@strahovka.ru']),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                );
                $obj->save();
                $user = $siteService->getUserData($obj->value);

                if ($user) {
                    $uwUserData = [
                        'login' => Arr::get($user, 'login'),
                        'subjectId' => Arr::get($user, 'subjectId'),
                    ];
                    $obj->value = json_encode(
                        array_merge($obj->value, $uwUserData),
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                    );
                    $obj->save();
                }
            }
        }

        $client = new Client();
        $url = config('services.uw.host');

        $params = [
            'product' => 'LIFE',
            'companyCode' => $contract->companyCode,
            'programCode' => $contract->program->programCode,
            'programUwCode' => $contract->program->programUwCode,
            'policyNumber' => $contract->number,
            'trafficSource' => $contract->trafficSource,
            'beginDate' => $contract->active_from,
            'endDate' => $contract->active_to,
            'premium' => $contract->premium,
            'insuredSum' => $contract->insured_sum,
            'object' => $contract->objects_value,
            'subject' => $contract->subject_value,
            'sberMerchantOrderNumber' => Payment::where('contract_id', $contract->id)->first(
            )->invoiceNum,

        ];

        Log::info(__METHOD__ . '. Params - ', [$url, $params]);

        try {
            $response = $client->post(
                $url . '/import_contract.php',
                [
                    'body' => json_encode($params, JSON_THROW_ON_ERROR),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            $json = [];
            $httpStatusCode = Response::HTTP_BAD_REQUEST;
            if (($e instanceof RequestException) && $e->getResponse()) {
                $json = json_decode(
                    $e->getResponse()->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
                $httpStatusCode = $e->getResponse()->getStatusCode();
            }
            Log::error(
                __METHOD__ . '. Exception:',
                [$httpStatusCode, $json, $e->getMessage(), $e->getTraceAsString()]
            );

            return false;
        } catch (Exception $e) {
            Log::error(
                __METHOD__ . '. ERROR - Response: ',
                [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]
            );

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);

            return false;
        }

        Log::info(
            __METHOD__ . '. Response',
            [
                json_decode(
                    $response->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
            ]
        );

        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Склоняем словоформу
     * @ author runcore
     */
    public static function morph($n, $f1, $f2, $f5)
    {
        $n = abs((int)$n) % 100;
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        $n %= 10;
        if ($n > 1 && $n < 5) {
            return $f2;
        }
        if ($n === 1) {
            return $f1;
        }

        return $f5;
    }

    /**
     * Возвращает название месяца на кириллице по номеру месяца.
     *
     * @param  int  $num
     *
     * @return string
     */
    public static function getCyrMonth(int $num): string
    {
        $arr = [
            'январь',
            'февраль',
            'март',
            'апрель',
            'май',
            'июнь',
            'июль',
            'август',
            'сентябрь',
            'октябрь',
            'ноябрь',
            'декабрь',
        ];

        return $arr[$num - 1];
    }

    /**
     * @param $request
     *
     * @return array|string|string[]
     */
    public static function getTrafficSource($request)
    {
        $domain = $request->headers->has('Origin') ? str_replace(
            ['http://', 'https://'],
            '',
            $request->headers->get('Origin')
        ) : 'strahovka.ru';

        if ($request->hasCookie('X-Split')) {
            $domain .= '.' . $request->cookie('X-Split');
        }

        $traffic_source_params = [
            $domain,
            $request->cookie('utm_campaign'),
            $request->cookie('utm_source'),
            $request->cookie('utm_medium'),
            $request->cookie('utm_content'),
            $request->cookie('utm_term'),
        ];

        $extra_params = array_filter(
            [
                $request->cookie('afclick'),
                $request->cookie('admitad_uid'),
                $request->cookie('adsbalance_id'),
                $request->cookie('click_id'),
                $request->cookie('subid1'),
                $request->cookie('subid2'),
                $request->cookie('subid3'),
            ]
        );

        $result = implode('//', $traffic_source_params) . '//';

        if (count($extra_params)) {
            $result .= implode('//', $extra_params);
        }

        $result = str_replace(['|', ':', ' '], [',', '=', ''], $result);

        if (static::isMobile()) {
            $result = 'm.' . $result;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function isMobile(): bool
    {
        $agent = new Agent();

        return $agent->isMobile() || $agent->isTablet();
    }
}
