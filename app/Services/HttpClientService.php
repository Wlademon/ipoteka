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

    /**
     * HttpClientService constructor.
     *
     * @param  string  $host
     * @param  array   $options
     * @param  string  $login
     * @param  string  $pass
     */
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
    public function getCurrentClient(): Client
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
    protected function getClient(array $options): Client
    {
        return new Client($options);
    }

    /**
     * @param  string  $url
     * @param  array   $data
     *
     * @return array|null
     * @throws \JsonException
     */
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
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        }
        if ($statusCode >= 400) {
            $this->lastError = json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return null;
    }

    /**
     * @param  string  $url
     *
     * @return mixed|null
     * @throws \JsonException
     */
    public function sendGetJson(string $url)
    {
        $response = $this->sendGet($url);
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            if ($statusCode > 400) {
                $this->lastError = json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            }

            return null;
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  string  $url
     *
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendGet(string $url): ResponseInterface
    {
        $this->lastError = null;

        return $this->getCurrentClient()->get($this->createUrl($url));
    }

    /**
     * @param  string  $url
     * @param  array   $options
     *
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPost(string $url, array $options): ResponseInterface
    {
        $this->lastError = null;

        return $this->getCurrentClient()->post($this->createUrl($url), $options);
    }

    /**
     * @param  string  $url
     * @param  array   $data
     *
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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
