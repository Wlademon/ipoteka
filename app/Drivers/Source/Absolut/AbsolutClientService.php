<?php

namespace App\Drivers\Source\Absolut;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;

class AbsolutClientService
{

    const TOKEN_URL = '/oauth/token';

    protected AbsolutClient $client;

    public function __construct(AbsolutClient $client)
    {
        $this->client = $client;
    }

    protected function getToken()
    {
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        $client = $this->getClient("Basic $auth");



        $client->post(
            self::TOKEN_URL,
            [
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'client_credentials',
                ],
            ]
        );
    }

    protected function getToken()
    {
        $key = $this->getCacheKey(__METHOD__);
        if (Cache::has($key)) {
            return Cache::get($key);
        }
    }

    protected function getCacheKey(string $prefix = '')
    {
        return $prefix . sha1(__METHOD__);
    }

    protected function send($client)
    {

    }

    protected function getClient(string $auth)
    {
        return new Client(
            [
                'base_uri' => new Uri($this->host),
                'headers' => ['Authorization' => $auth,],
            ]
        );
    }
}