<?php

namespace App\Drivers\Source\Alpha;

use App\Exceptions\Drivers\AlphaException;
use GuzzleHttp\Client;

/**
 * Class AlfaAuth
 *
 * @package App\Drivers\Source\Alpha
 */
class AlfaAuth
{
    public const GRANT_TYPE = 'password';
    protected string $username;
    protected string $pass;
    protected string $url;

    /**
     * AlfaAuth constructor.
     *
     * @param  string  $username
     * @param  string  $pass
     * @param  string  $url
     */
    public function __construct(string $username, string $pass, string $url)
    {
        $this->username = $username;
        $this->pass = $pass;
        $this->url = $url;
    }

    /**
     * @param  Client  $client
     *
     * @return mixed
     * @throws AlphaException|\GuzzleHttp\Exception\GuzzleException|\JsonException
     */
    public function getToken(Client $client): string
    {
        $params = [
            'username' => $this->username,
            'password' => $this->pass,
            'grant_type' => self::GRANT_TYPE,
        ];
        \Log::debug(
            __METHOD__ . ' получение токена',
            [
                'url' => $this->url,
                'request' => $params,
            ]
        );
        $result = $client->post(
            $this->url,
            [
                'form_params' => $params,
            ]
        );
        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error auth');
        }
        $response = $result->getBody()->getContents();
        \Log::debug(
            __METHOD__ . ' токен получен',
            [
                'response' => $response,
            ]
        );

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }
}
