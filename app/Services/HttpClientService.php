<?php

namespace App\Services;

use App\Drivers\Source\Renins\TokenService;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpClientService
 *
 * @package App\Services
 */
class HttpClientService
{
    protected string $host;
    protected ?Client $client = null;
    protected $lastError;
    protected array $options;
    protected ?string $token = null;
    protected string $login;
    protected string $pass;

    public function __construct(string $host, array $options, string $login, string $pass)
    {
        $this->host = $host;
        $this->options = $options;
        $this->login = $login;
        $this->pass = $pass;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @return Client
     */
    public function getCurretClient(): Client
    {
        if (!$this->client) {
            $this->client = $this->getClient($this->options);
        }

        return $this->client;
    }

    /**
     * @return $this
     * @throws \App\Exceptions\Drivers\ReninsException
     */
    public function withToken(): self
    {
        $options = $this->options;
        if (!$this->token) {
            $this->token = TokenService::getToken($this->host, $this->login, $this->pass);
        }
        $options['headers'] = [
            'Authorization' => "Bearer {$this->token}",
        ];
        $this->client = $this->getClient($options);

        return $this;
    }

    /**
     * @param  array  $options
     *
     * @return Client
     */
    protected function getClient(array $options)
    {
        return new Client($options);
    }

    public function sendJson(string $url, array $data): ?array
    {
        $response = $this->sendPost(
            $url,
            [
                'json' => $data,
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            return json_decode($response->getBody()->getContents(), true);
        }
        if ($statusCode >= 400) {
            $this->lastError = json_decode($response->getBody()->getContents(), true);
        }

        return null;
    }

    /**
     * @param  string  $url
     *
     * @return mixed|null
     */
    public function sendGetJson(string $url)
    {
        $response = $this->sendGet($url);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            if ($statusCode > 400) {
                $this->lastError = json_decode($response->getBody()->getContents(), true);
            }

            return null;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    public function sendGet(string $url): ResponseInterface
    {
        $this->lastError = null;

        return $this->getCurretClient()->get($this->createUrl($url));
    }

    public function sendPost(string $url, array $options): ResponseInterface
    {
        $this->lastError = null;

        return $this->getCurretClient()->post($this->createUrl($url), $options);
    }

    public function sendJsonGetFile(string $url, array $data): ?string
    {
        $response = $this->sendPost(
            $url,
            [
                'json' => $data,
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            return $response->getBody()->getContents();
        }

        return null;
    }

    /**
     * @param $url
     *
     * @return string
     */
    protected function createUrl($url): string
    {
        return trim($this->host, '/') . '/' . trim($url, '/&?');
    }
}
