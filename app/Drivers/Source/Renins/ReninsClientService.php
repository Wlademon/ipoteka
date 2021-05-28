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
 * @package App\Drivers\Source\Renins
 */
class ReninsClientService
{
    const URL_CALCULATE = '/IpotekaAPI/1.0.0/calculate';
    const URL_PRINT = '/IpotekaAPI/1.0.0/print';
    const URL_SAVE = '/IpotekaAPI/1.0.0/save';
    const URL_PAY = '/IpotekaAPI/1.0.0/getPaymentLink';
    const URL_ISSUE = '/IpotekaAPI/1.0.0/issue';
    const URL_STATUS = '/IpotekaAPI/1.0.0/getIssueProcessStatus';
    const URL_IMPORT = '/IpotekaAPI/1.0.0/import';

    const ISSUE_ERROR = 'ISSUE_ERROR';

    const TEMP_PATH = 'temp/';

    protected HttpClientService $client;

    /**
     * ReninsClientService constructor.
     * @param Repository $repository
     * @param string $prefix
     * @throws ReninsException
     */
    public function __construct(Repository $repository, string $prefix = '')
    {
        $host = $repository->get($prefix . 'host');
        $login = $repository->get($prefix . 'login');
        $pass = $repository->get($prefix . 'pass');
        if (!$host) {
            throw new ReninsException('Not set Renisans host.');
        }
        $token = TokenService::getToken($host, $login, $pass);
        if (!$token) {
            throw new ReninsException('Not set Renisans token.');
        }
        $this->client = HttpClientService::create(
            $host,
            [
                'curl'   => [CURLOPT_SSL_VERIFYPEER => false],
                'verify' => false,
                'headers' => [
                    'Authorization' => "Bearer $token"
                ],
            ],
        );
    }

    /**
     * @param Arrayable $data
     * @param string $url
     * @return array|null
     * @throws ReninsException
     */
    protected function send(Arrayable $data, string $url): ?array
    {
        try {
            $result = $this->client->sendJson($url, $data->toArray());
        } catch (Throwable $throwable) {
            throw new ReninsException($throwable->getMessage());
        }
        if ($this->client->getLastError()) {
            throw new ReninsException($this->client->getLastError());
        }

        return $result;
    }

    /**
     * @param Arrayable $data
     * @return array|null
     * @throws ReninsException
     */
    public function calculate(Arrayable $data)
    {
        $result = $this->send($data, self::URL_CALCULATE);
        if ($errors = Arr::get($result, 'calcPolicyResult.calcResults.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    /**
     * @param Arrayable $data
     * @return array|null
     * @throws ReninsException
     */
    public function import(Arrayable $data): ?array
    {
        $result = $this->send($data, self::URL_IMPORT);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    /**
     * @param Arrayable $data
     * @return array|null
     * @throws ReninsException
     */
    public function issue(Arrayable $data): ?array
    {
        $result = $this->send($data, self::URL_ISSUE);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    /**
     * @param Arrayable $data
     * @return array|null
     * @throws ReninsException
     */
    public function payLink(Arrayable $data): ?array
    {
        $result = $this->send($data, self::URL_PAY);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    /**
     * @param Arrayable $data
     * @return string
     * @throws ReninsException
     */
    public function getStatus(Arrayable $data): string
    {
        $result = $this->send($data, self::URL_STATUS);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
        if (($state = Arr::get($result, 'state')) === self::ISSUE_ERROR) {
            throw new ReninsException(json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return $state;
    }

    /**
     * @param Arrayable $data
     * @return string
     * @throws ReninsException
     */
    public function print(Arrayable $data): string
    {
        $result = $this->send($data, self::URL_PRINT);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return Arr::get($result, 'url');
    }

    /**
     * @param string $url
     * @return string
     * @throws ReninsException
     */
    public function getFile(string $url): string
    {
        try {
            $response = (new Client([
                    'curl'   => [CURLOPT_SSL_VERIFYPEER => false],
                    'verify' => false,
                ]))->get($url);
            $content = $response->getBody()->getContents();
            throw_if(
                $response->getStatusCode() !== 200,
                ReninsException::class,
                [$content]
            );
            $path = self::TEMP_PATH . uniqid(date('Y_m_d_H_i_s'), false) . '.zip';
            Storage::put(
                $path,
                $content
            );
            throw_if(
                !file_exists(storage_path('app/' . $path)),
                ReninsException::class,
                ['File not saved.']
            );

        } catch (Throwable $throwable) {
            throw new ReninsException($throwable->getMessage());
        }

        return 'app/' . $path;
    }
}

