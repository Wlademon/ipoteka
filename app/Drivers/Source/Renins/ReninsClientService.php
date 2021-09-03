<?php

namespace App\Drivers\Source\Renins;

use App\Exceptions\Drivers\ReninsException;
use App\Services\HttpClientService;
use GuzzleHttp\Client;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Class ReninsClientService
 *
 * @package App\Drivers\Source\Renins
 */
class ReninsClientService
{
    const ISSUE_ERROR = 'ISSUE_ERROR';
    protected string $tempPath;
    protected array $actions;
    protected HttpClientService $client;

    /**
     * ReninsClientService constructor.
     *
     * @param  Repository  $repository
     * @param  string      $prefix
     *
     * @throws ReninsException
     */
    public function __construct(HttpClientService $clientService, array $actions, string $tempPath)
    {
        $this->actions = $actions;
        $this->client = $clientService;
        $this->tempPath = $tempPath;
    }

    /**
     * @param  Arrayable  $data
     * @param  string     $url
     *
     * @return array|null
     * @throws ReninsException
     */
    protected function send(Arrayable $data, string $url): ?array
    {
        try {
            $result = $this->client->withToken()->sendJson($url, $data->toArray());
        } catch (Throwable $throwable) {
            throw new ReninsException(
                __METHOD__, $throwable->getMessage() . gettype($this->client)
            );
        }
        if ($this->client->getLastError()) {
            throw new ReninsException(__METHOD__, $this->client->getLastError());
        }

        return $result;
    }

    /**
     * @param  Arrayable  $data
     *
     * @return array|null
     * @throws ReninsException
     */
    public function calculate(Arrayable $data)
    {
        \Log::info(
            __METHOD__ . ' расчет полиса',
            [
                'request' => $data->toArray(),
            ]
        );
        $result = $this->send($data, Arr::get($this->actions, 'URL_CALCULATE'));
        if ($errors = Arr::get($result, 'calcPolicyResult.calcResults.errors')) {
            throw new ReninsException(__METHOD__, json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        \Log::info(
            __METHOD__ . ' расчет полиса завершен',
            [
                'response' => $result,
            ]
        );

        return $result;
    }

    /**
     * @param  Arrayable  $data
     *
     * @return array|null
     * @throws ReninsException
     */
    public function import(Arrayable $data): ?array
    {
        \Log::info(
            __METHOD__ . ' сохранение полиса',
            [
                'request' => $data->toArray(),
            ]
        );
        $result = $this->send($data, Arr::get($this->actions, 'URL_IMPORT'));
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(__METHOD__, json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        \Log::info(
            __METHOD__ . ' сохранение полиса завершено',
            [
                'response' => $result,
            ]
        );

        return $result;
    }

    /**
     * @param  Arrayable  $data
     *
     * @return array|null
     * @throws ReninsException
     */
    public function issue(Arrayable $data): ?array
    {
        \Log::info(
            __METHOD__ . '  оформление полиса',
            [
                'request' => $data->toArray(),
            ]
        );
        $result = $this->send($data, Arr::get($this->actions, 'URL_ISSUE'));
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(__METHOD__, json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        \Log::info(
            __METHOD__ . '  оформление полиса завершено',
            [
                'response' => $result,
            ]
        );

        return $result;
    }

    /**
     * @param  Arrayable  $data
     *
     * @return array|null
     * @throws ReninsException
     */
    public function payLink(Arrayable $data): ?array
    {
        \Log::info(
            __METHOD__ . ' получение ссылки на оплату',
            [
                'request' => $data->toArray(),
            ]
        );
        $result = $this->send($data, Arr::get($this->actions, 'URL_PAY'));
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(__METHOD__, json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        \Log::info(
            __METHOD__ . ' ссылка на оплату получена',
            [
                'response' => $result,
            ]
        );

        return $result;
    }

    /**
     * @param  Arrayable  $data
     *
     * @return string
     * @throws ReninsException
     */
    public function getStatus(Arrayable $data): string
    {
        \Log::info(
            __METHOD__ . ' получение статуса',
            [
                'request' => $data->toArray(),
            ]
        );
        $result = $this->send($data, Arr::get($this->actions, 'URL_STATUS'));
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(__METHOD__, json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        if (($state = Arr::get($result, 'state')) === self::ISSUE_ERROR) {
            throw new ReninsException(__METHOD__, json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        \Log::info(
            __METHOD__ . ' статус получен',
            [
                'response' => $result,
            ]
        );

        return $state;
    }

    /**
     * @param  Arrayable  $data
     *
     * @return string
     * @throws ReninsException
     */
    public function print(Arrayable $data): string
    {
        \Log::info(
            __METHOD__ . ' получение ссылки на полис',
            [
                'request' => $data->toArray(),
            ]
        );
        $result = $this->send($data, Arr::get($this->actions, 'URL_PRINT'));
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(__METHOD__, json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        \Log::info(
            __METHOD__ . ' ссылка на полис получена',
            [
                'response' => $result,
            ]
        );

        return Arr::get($result, 'url');
    }

    /**
     * @param  string  $url
     *
     * @return string
     * @throws ReninsException
     */
    public function getFile(string $url): string
    {
        \Log::info(
            __METHOD__ . ' получение файла с полисами',
            [
                'url' => $url,
            ]
        );
        try {
            $response = (new Client(
                [
                    'curl' => [CURLOPT_SSL_VERIFYPEER => false],
                    'verify' => false,
                ]
            ))->get($url);
            $content = $response->getBody()->getContents();
            throw_if($response->getStatusCode() !== 200, new ReninsException(__METHOD__, $content));
            $path = $this->tempPath . uniqid(date('Y_m_d_H_i_s'), false) . '.zip';
            Storage::put(
                $path,
                $content
            );
            throw_if(
                !file_exists(storage_path('app/' . $path)),
                new ReninsException(__METHOD__, 'File not saved.')
            );
        } catch (Throwable $throwable) {
            throw new ReninsException(__METHOD__, $throwable->getMessage());
        }
        \Log::info(__METHOD__ . ' файл получен и сохранен: ' . storage_path('app/' . $path));

        return 'app/' . $path;
    }
}

