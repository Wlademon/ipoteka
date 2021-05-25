<?php


namespace App\Drivers\Source\Alpha;


use App\Exceptions\Drivers\AlphaException;

class AlfaAuth
{

    const AUTH_URL = 'https://b2b-test2.alfastrah.ru/msrv/oauth/token?';
    const USERNAME = 'E_PARTNER';
    const PASSWORD = 'ALFAE313';
    const GRANT_TYPE = 'password';

    public function getToken($client)
    {
        $result = $client->post(
            self::AUTH_URL, [
                'form_params' => [
                    'username' => self::USERNAME,
                    'password' => self::PASSWORD,
                    'grant_type' => self::GRANT_TYPE
                ]
            ]
        );
        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error auth');
        }

        return (json_decode($result->getBody()->getContents(), true));
    }
}
