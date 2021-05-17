<?php

namespace App\Drivers\Source\Renins;

use App\Services\HttpClientService;
use Illuminate\Support\Facades\Cache;

class TokenService
{
    const URL_AUTHORIZE = '/token';

    protected HttpClientService $client;
    protected $login;
    protected $pass;

    protected function __construct($host, string $login, string $pass)
    {
        $this->login = $login;
        $this->pass = $pass;
        $this->client = HttpClientService::create(
            $host,
            [
               'headers' => [
                   'Authorization' => "Basic " . base64_encode("$login:$pass")
               ]
           ]
        );
    }

    public static function getToken(
        string $host,
        string $login,
        string $pass
    ): string {
        $tokenGetter = new static($host, $login, $pass);
        $token = $tokenGetter->getCacheToken();
        if ($token) {
            return $token;
        }

        $token = $tokenGetter->authorize();
        $tokenGetter->cacheToken($token);

        return $token;
    }

    protected function getCacheToken(): ?string
    {
        $key = $this->hash();

        return Cache::get($key);
    }

    protected function hash(): string
    {
        return \hash('sha256', $this->login . $this->pass);
    }

    protected function authorize(): string
    {
        $result = $this->client->sendPost(
            self::URL_AUTHORIZE,
            [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ]
            ]
        );

        if ($result->getStatusCode() > 300) {
            throw new \Exception($this->client->getLastError()['error_description']);
        }

        return json_decode($result->getBody()->getContents(), true)['access_token'];
    }

    protected function cacheToken($token): void
    {
        $key = $this->hash();
        Cache::set($key, $token, 300);
    }
}
