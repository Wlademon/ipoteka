<?php

namespace App\Drivers\Source\Renins;

use App\Services\HttpClientService;
use Illuminate\Contracts\Support\Jsonable;
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
        return 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsIng1dCI6IlpqUm1ZVE13TlRKak9XVTVNbUl6TWpnek5ESTNZMkl5TW1JeVkyRXpNamRoWmpWaU1qYzBaZz09In0.eyJhdWQiOiJodHRwOlwvXC9vcmcud3NvMi5hcGltZ3RcL2dhdGV3YXkiLCJzdWIiOiJiZXJlZ25veXZhQGNhcmJvbi5zdXBlciIsImFwcGxpY2F0aW9uIjp7Im93bmVyIjoiYmVyZWdub3l2YSIsInRpZXJRdW90YVR5cGUiOiJyZXF1ZXN0Q291bnQiLCJ0aWVyIjoiMTBQZXJNaW4iLCJuYW1lIjoiR2VuMSIsImlkIjozNjgsInV1aWQiOm51bGx9LCJzY29wZSI6ImFtX2FwcGxpY2F0aW9uX3Njb3BlIGRlZmF1bHQiLCJpc3MiOiJodHRwczpcL1wvYXBpLnJlbmlucy5jb21cL29hdXRoMlwvdG9rZW4iLCJ0aWVySW5mbyI6eyJVbmxpbWl0ZWQiOnsidGllclF1b3RhVHlwZSI6InJlcXVlc3RDb3VudCIsInN0b3BPblF1b3RhUmVhY2giOnRydWUsInNwaWtlQXJyZXN0TGltaXQiOjAsInNwaWtlQXJyZXN0VW5pdCI6bnVsbH19LCJrZXl0eXBlIjoiU0FOREJPWCIsInN1YnNjcmliZWRBUElzIjpbeyJzdWJzY3JpYmVyVGVuYW50RG9tYWluIjoiY2FyYm9uLnN1cGVyIiwibmFtZSI6Iklwb3Rla2FBUEkiLCJjb250ZXh0IjoiXC9JcG90ZWthQVBJXC8xLjAuMCIsInB1Ymxpc2hlciI6ImFwaVRlc3QiLCJ2ZXJzaW9uIjoiMS4wLjAiLCJzdWJzY3JpcHRpb25UaWVyIjoiVW5saW1pdGVkIn1dLCJjb25zdW1lcktleSI6IjhORE4wNGQ1QTdTQmhqc1lNY1laMDdyUmxqb2EiLCJleHAiOjE2NTc1MTI4MzksImlhdCI6MTYyMTUxMjgzOSwianRpIjoiNTRjZGU3ZjUtYmY3Yy00ZmYwLWJlODctZDVmOWFmYmIyZTZiIn0.dJC5PcSqMYztmH2proMcXwVK2az1sy4k6YYbOmxVqaY-4ojryXMb0LnW5FiA5U--NT6QWclsZMZqhI2SW_fjAmqNZH6qshoadoj_YPIvXrD3p_15n8rHUqI_f9WLWcpC4wvX8aaDgLQM3vUP8_65WisecZkzjHGE5ONq4iEfXf1a17rexSXhaWmhbx_QS_ajzPVj0c6P1BN5nBxM-riAlfWErEfZkWf00_PwEilEYDZ5_1l1Fw8CV_JhPS6Q_mUpfFCfwk2KDPITqDvuGL3lS0DL5G88poKca6cep-C4UZ3ov2vy6Xrvxm0r60_YcCN_Gtgz0qVfeGP924GcwT-UXg';

        $tokenGetter = new static($host, $login, $pass);
        $token = $tokenGetter->getCacheToken();
        if (false && $token) {
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
