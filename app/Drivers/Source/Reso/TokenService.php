<?php

namespace App\Drivers\Source\Reso;

use App\Services\HttpClientService;
use Illuminate\Support\Facades\Cache;

class TokenService
{
    const URL_AUTHORIZE = '/am/auth/v2/authorize';
    const MODE_AUTHORIZE = 1;

    protected HttpClientService $client;
    protected $login;
    protected $pass;

    protected function __construct(HttpClientService $client, string $login, string $pass)
    {
        $this->client = $client;
        $this->login = $login;
        $this->pass = $pass;
    }

    public static function getToken(
        HttpClientService $client,
        string $login,
        string $pass
    ): string {
        $tokenGetter = new static($client, $login, $pass);
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
        $result = $this->client->sendJson(
            self::URL_AUTHORIZE,
            [
                'username' => $this->login,
                'password' => $this->pass,
                'mode' => self::MODE_AUTHORIZE
            ]
        );
        if ($this->client->getLastError()) {
            throw new \Exception($this->client->getLastError()['MESSAGE']);
        }

        return $result['ACCESS_TOKEN'];
    }

    protected function cacheToken($token): void
    {
        $key = $this->hash();
        Cache::set($key, $token, 300);
    }
}
