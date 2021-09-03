<?php

namespace App\Drivers\Source\Renins;

use App\Exceptions\Drivers\ReninsException;
use App\Services\HttpClientService;
use Illuminate\Support\Facades\Cache;

use function hash;

/**
 * Class TokenService
 *
 * @package App\Drivers\Source\Renins
 */
class TokenService
{
    const URL_AUTHORIZE = '/token';
    protected HttpClientService $client;
    protected string $login;
    protected string $pass;

    /**
     * TokenService constructor.
     *
     * @param  string  $host
     * @param  string  $login
     * @param  string  $pass
     */
    protected function __construct(string $host, string $login, string $pass)
    {
        $this->login = $login;
        $this->pass = $pass;
        $this->client = new HttpClientService(
            $host, [
                     'headers' => [
                         'Authorization' => 'Basic ' . base64_encode("{$login}:{$pass}"),
                     ],
                 ], $login, $pass
        );
    }

    /**
     * @param  string  $host
     * @param  string  $login
     * @param  string  $pass
     *
     * @return string
     * @throws ReninsException
     */
    public static function getToken(string $host, string $login, string $pass): string
    {
        $tokenGetter = new static($host, $login, $pass);
        $token = $tokenGetter->getCacheToken();
        if ($token) {
            return $token;
        }

        $token = $tokenGetter->authorize();
        $tokenGetter->cacheToken($token);

        return $token;
    }

    /**
     * @return string|null
     */
    protected function getCacheToken(): ?string
    {
        $key = $this->hash();

        return Cache::get($key);
    }

    /**
     * @return string
     */
    protected function hash(): string
    {
        return hash('sha256', $this->login . $this->pass);
    }

    /**
     * @return string
     * @throws ReninsException
     */
    protected function authorize(): string
    {
        $result = $this->client->sendPost(
            self::URL_AUTHORIZE,
            [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        if ($result->getStatusCode() > 300) {
            throw new ReninsException(
                __METHOD__, $this->client->getLastError()['error_description']
            );
        }

        return json_decode($result->getBody()->getContents(), true)['access_token'];
    }

    /**
     * @param $token
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function cacheToken(string $token): void
    {
        $key = $this->hash();
        Cache::set($key, $token, 300);
    }
}
