<?php


namespace App\Helpers;

use App\Exceptions\Services\PolicyServiceException;
use App\Models\BaseModel;
use App\Models\Contracts;
use App\Models\Payments;
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


class Helper
{
    /** @var BaseModel[] $models */
    private static $models = [];

    /**
     * Получить список всех моделей
     * @return array
     */
    public static function getAllModels()
    {
        if (count(self::$models) == 0) {
            self::addClassesFromPathsArray(glob(base_path() . '/app/Models/*.php'));
        }

        return self::$models;
    }

    private static function addClassesFromPathsArray(array $filePaths)
    {
        foreach ($filePaths as $filePath) {
            $modelName = self::getClassFromFilePath($filePath);
            if (
                $modelName != ''
                && class_exists($modelName)
            ) {
                self::$models[] = $modelName;
            }
        }
    }

    /**
     * Получить имя класса по пути файла
     *
     * @param string $path
     *
     * @return string
     */
    public static function getClassFromFilePath($path)
    {
        $class = preg_replace('/.*app\\//', 'App/', $path);
        $class = str_replace('/', '\\', $class);
        $class = str_replace('.php', '', $class);

        return $class;
    }

    /**
     * @param $field
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
     * @param array $currentModel
     * @param array $newModel
     *
     * @return array
     */
    public static function getUpdatesModelParams(array $currentModel, array $newModel)
    {
        /** @var Model $currentModel */
        $updatedParams = [];
        foreach ($newModel as $param => $value) {
            if ($param == '_token' || $param == 'updated_at') {
                continue;
            }
            if (is_array($value) && isset($currentModel[$param]) && is_array($currentModel[$param])) {
                $innerParams = self::getUpdatesModelParams($currentModel[$param], $value);
                if (count($innerParams) > 0) {
                    $updatedParams[$param] = $value;
                    continue;
                }
            }
            if ($param == 'password' || !is_string($param)) {
                $updatedParams[$param] = $value;
                continue;
            }
            if (!isset($currentModel[$param]) || $newModel[$param] !== $currentModel[$param]) {
                $updatedParams[$param] = $value;
            }
        }

        return $updatedParams;
    }

    /**
     * Получить новый номер полиса
     * @param $params
     * @return mixed|void
     */
    public static function getPolicyNumber($params)
    {
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'testing') {
            return json_decode(json_encode(['data' => ['bso_numbers' => ['Z6921/397/RU0000/20']]]));
        }
        $client = new Client();
        $url = env('BISHOP_HOST', 'https://bishop.strahovka.ru') . '/bso';

        Log::info(__METHOD__ . '. Params - ', [$url, $params]);
        $httpStatusCode = Response::HTTP_BAD_REQUEST;

        try {
            $response = $client->post(
                $url,
                [
                    'body' => json_encode($params),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            $json = [];
            if ($e instanceof RequestException) {
                if (!empty($e->getResponse())) {
                    $json = json_decode($e->getResponse()->getBody(), true);
                    $httpStatusCode = $e->getResponse()->getStatusCode();
                }
            }
            Log::error(__METHOD__ . '. Exception:', [$json, $e->getMessage(), $e->getTraceAsString()]);

            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        } catch (Exception $e) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);

            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);

            throw new PolicyServiceException('ERROR - Response: ' . json_encode($response->getBody()), $response->getStatusCode());
        }
        Log::info(__METHOD__ . '. Response', [json_decode($response->getBody())]);

        return json_decode($response->getBody());
    }

    /**
     * Подтвердить номер полиса
     * @param $params
     * @return mixed|void
     */
    public static function acceptPolicyNumber($params)
    {
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'testing') {
            return true;
        }
        $client = new Client();
        $url = env('BISHOP_HOST', 'https://bishop.strahovka.ru') . '/bso/accept';

        Log::info(__METHOD__ . '. Params - ', [$url, $params]);
        $httpStatusCode = Response::HTTP_BAD_REQUEST;

        try {
            $response = $client->post(
                $url,
                [
                    'body' => json_encode($params),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            $json = [];
            if ($e instanceof RequestException) {
                if (!empty($e->getResponse())) {
                    $json = json_decode($e->getResponse()->getBody(), true);
                    $httpStatusCode = $e->getResponse()->getStatusCode();
                }
            }
            Log::error(__METHOD__ . '. Exception:', [$json, $e->getMessage(), $e->getTraceAsString()]);
            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        } catch (Exception $e) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
            throw new PolicyServiceException($e->getMessage(), $httpStatusCode);
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);
            throw new PolicyServiceException('ERROR - Response: ' . json_encode($response->getBody()), $response->getStatusCode());
        }
        Log::info(__METHOD__ . '. Response', [json_decode($response->getBody())]);
        return json_decode($response->getBody());
    }

    /**
     * Экспорт полиса в Uwin - вернет номер ContractId
     *
     * @param Contracts $contract
     * @return mixed|void
     */
    public static function getUwinContractId(Contracts $contract)
    {
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'testing') {
            return json_decode(json_encode(['contractId' => '111111']));
        }
        $siteService = new SiteService();

        $subject = $contract->subject;
        if (!isset($subject->value['subjectId'])) {
            Log::info(__METHOD__ . ". getUserData subject", [$subject->value]);
            $user = $siteService->getUserData($subject->value);
            if ($user) {
                $uwUserData = [
                    'login' => Arr::get($user, 'login'),
                    'subjectId' => Arr::get($user, 'subjectId')
                ];
                $subject->value = json_encode(array_merge($subject->value, $uwUserData), JSON_UNESCAPED_UNICODE);
                $subject->save();
            }
        }
        foreach ($contract->objects as $obj) {
            if (!isset($obj->value['subjectId'])) {
                Log::info(__METHOD__ . ". getUserData object", [$obj->value]);
                $code = md5(
                    $obj->value['lastName'] . $obj->value['firstName'] . ($obj->value['middleName'] ?? '') . $obj->value['birthDate'] . time(
                    )
                );
                $obj->value = json_encode(
                    array_merge($obj->value, ['email' => $code . '@strahovka.ru']),
                    JSON_UNESCAPED_UNICODE
                );
                $obj->save();
                $user = $siteService->getUserData($obj->value);

                if ($user) {
                    $uwUserData = [
                        'login' => Arr::get($user, 'login'),
                        'subjectId' => Arr::get($user, 'subjectId'),
                    ];
                    $obj->value = json_encode(array_merge($obj->value, $uwUserData), JSON_UNESCAPED_UNICODE);
                    $obj->save();
                }
            }
        }

        $client = new Client();
        $url = env('UW_HOST', 'http://uw.stage.strahovka.ru');

        $params = [
            "product" => "LIFE",
            "companyCode" => $contract->companyCode,
            "programCode" => $contract->program->programCode,
            "programUwCode" => $contract->program->programUwCode,
            "policyNumber" => $contract->number,
            "trafficSource" => $contract->trafficSource,
            "beginDate" => $contract->active_from,
            "endDate" => $contract->active_to,
            "premium" => $contract->premium,
            "insuredSum" => $contract->insured_sum,
            "object" => $contract->objects_value,
            "subject" => $contract->subject_value,
            'sberMerchantOrderNumber' => Payments::whereContractId($contract->id)->first()->invoiceNum,

        ];

        Log::info(__METHOD__ . '. Params - ', [$url, $params]);

        try {
            $response = $client->post(
                $url . '/import_contract.php',
                [
                    'body' => json_encode($params),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            $json = [];
            $httpStatusCode = Response::HTTP_BAD_REQUEST;
            if ($e instanceof RequestException) {
                if ($e->getResponse()) {
                    $json = json_decode($e->getResponse()->getBody(), true);
                    $httpStatusCode = $e->getResponse()->getStatusCode();
                }
            }
            Log::error(
                __METHOD__ . '. Exception:',
                [$httpStatusCode, $json, $e->getMessage(), $e->getTraceAsString()]
            );
            return false;
        } catch (Exception $e) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);

            return false;
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);

            return false;
        }

        Log::info(__METHOD__ . '. Response', [json_decode($response->getBody())]);

        return json_decode($response->getBody());
    }

    /**
     * Склоняем словоформу
     * @ author runcore
     */
    public static function morph($n, $f1, $f2, $f5)
    {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        $n = $n % 10;
        if ($n > 1 && $n < 5) {
            return $f2;
        }
        if ($n == 1) {
            return $f1;
        }
        return $f5;
    }

    /**
     * Возвращает название месяца на кириллице по номеру месяца.
     * @param $num
     * @return string
     */
    public static function getCyrMonth($num)
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
            'декабрь'
        ];
        return $arr[$num - 1];
    }

    /**
     * @param $request
     * @return mixed|string
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

        $traffic_source_params = array(
            $domain,
            $request->cookie('utm_campaign'),
            $request->cookie('utm_source'),
            $request->cookie('utm_medium'),
            $request->cookie('utm_content'),
            $request->cookie('utm_term'),
        );

        $extra_params = array_filter(
            array(
                $request->cookie('afclick'),
                $request->cookie('admitad_uid'),
                $request->cookie('adsbalance_id'),
            )
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

    static function isMobile()
    {
        $agent = new Agent();
        if ($agent->isMobile() || $agent->isTablet()) {
            return true;
        }
        return false;
    }
}
