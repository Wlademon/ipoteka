<?php

namespace App\Services;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class HttpClientService
{
    protected string $host;

    protected Client $client;

    protected $lastError;

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    public function getCurretClient()
    {
        return $this->client;
    }

    protected function __construct(string $host, array $options)
    {
        $this->host = $host;
        $this->client = $this->getClient($options);
    }

    protected function getClient(array $options)
    {
        return new Client($options);
    }

    public function sendJson(string $url, array $data): ?array
    {
        $response = $this->sendPost($url, [
            'json' => $data
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            return json_decode($response->getBody()->getContents(), true);
        }
        if ($statusCode >= 400) {
            $this->lastError = json_decode($response->getBody()->getContents(), true);
        }

        return null;
    }

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

        return $this->client->get($this->createUrl($url));
    }

    public function sendPost(string $url, array $options): ResponseInterface
    {
        $this->lastError = null;

        return $this->client->post($this->createUrl($url), $options);
    }

    public function sendJsonGetFile(string $url, array $data): ?string
    {
        $response = $this->sendPost($url, [
            'json' => $data
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            return $response->getBody()->getContents();
        }

        return null;
    }

    protected function createUrl($url): string
    {
        return trim($this->host, '/') . '/' . trim($url, '/&?');
    }

    public static function create($host, $options = []): HttpClientService
    {
        return new static($host, $options);
    }
}
