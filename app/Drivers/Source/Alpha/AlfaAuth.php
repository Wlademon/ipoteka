<?php

namespace App\Drivers\Source\Alpha;

use App\Exceptions\Drivers\AlphaException;
use GuzzleHttp\Client;

/**
 * Class AlfaAuth
 * @package App\Drivers\Source\Alpha
 */
class AlfaAuth
{
    const GRANT_TYPE = 'password';

    protected string $username;
    protected string $pass;
    protected string $url;

    /**
     * AlfaAuth constructor.
     * @param string $username
     * @param string $pass
     * @param string $url
     */
    public function __construct(string $username, string $pass, string $url)
    {
        $this->username = $username;
        $this->pass = $pass;
        $this->url = $url;
    }

    /**
     * @param Client $client
     * @return mixed
     * @throws AlphaException
     */
    public function getToken(Client $client)
    {
        $result = $client->post($this->url, [
            'form_params' => [
                'username' => $this->username,
                'password' => $this->pass,
                'grant_type' => self::GRANT_TYPE,
            ]
        ]);
        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error auth');
        }

        return json_decode($result->getBody()->getContents(), true);
    }
}
