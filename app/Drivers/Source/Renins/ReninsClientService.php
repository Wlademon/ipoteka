<?php

namespace App\Drivers\Source\Renins;

use App\Exceptions\Drivers\ReninsException;
use App\Services\HttpClientService;
use http\Client;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ReninsClientService
{
    const URL_CALCULATE = '/IpotekaAPI/1.0.0/calculate';
    const URL_PRINT = '/IpotekaAPI/1.0.0/print';
    const URL_SAVE = '/IpotekaAPI/1.0.0/save';
    const URL_PAY = '/IpotekaAPI/1.0.0/getPaymentLink';
    const URL_ISSUE = '/IpotekaAPI/1.0.0/issueAsync';
    const URL_STATUS = '/IpotekaAPI/1.0.0//getIssueProcessStatus';
    const URL_IMPORT = '/IpotekaAPI/1.0.0/import';

    const ISSUE_ERROR = 'ISSUE_ERROR';

    const TEMP_PATH = 'temp/';

    protected HttpClientService $client;

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
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ],
            ],
        );
    }

    protected function send(Arrayable $data, string $url)
    {
        try {
            $result = $this->client->sendJson($url, $data->toArray());
        } catch (\Throwable $throwable) {
            throw new ReninsException($throwable->getMessage());
        }
        if ($this->client->getLastError()) {
            throw new ReninsException($this->client->getLastError());
        }

        return $result;
    }

    public function calculate(Arrayable $data)
    {
        $result = $this->send($data, self::URL_CALCULATE);
        if ($errors = Arr::get($result, 'calcPolicyResult.calcResults.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    public function import(Arrayable $data)
    {
        $result = $this->send($data, self::URL_IMPORT);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    public function issueAsync(Arrayable $data)
    {
        $result = $this->send($data, self::URL_ISSUE);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

    public function payLink(Arrayable $data)
    {
        $result = $this->send($data, self::URL_PAY);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return $result;
    }

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

    public function print(Arrayable $data)
    {
        $result = $this->send($data, self::URL_PRINT);
        if ($errors = Arr::get($result, 'errors.errors')) {
            throw new ReninsException(json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return Arr::get($result, 'url');
    }

    public function getFile(string $url): string
    {
        try {
            $response = (new \GuzzleHttp\Client())->get($url);
            throw_if(
                $response->getStatusCode() !== 200,
                ReninsException::class,
                [$response->getBody()->getContents()]
            );
            Storage::put(
                ($path = self::TEMP_PATH . uniqid(date('Y_m_d_H_i_s'), false) . '.zip'),
                $response->getBody()->getContents()
            );
        } catch (\Throwable $throwable) {
            throw new ReninsException($throwable->getMessage());
        }

        return $path;
    }
}

