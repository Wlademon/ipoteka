<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SiteService extends Service
{
    /**
     * Получить нового пользователя
     *
     * @param $params
     * @return mixed
     */
    public function getUserData($params)
    {
        if (config('app.env') === 'local' || config('app.env') === 'testing') {
            return  ['login' => 'TestUser', 'subjectId' => 1, 'trafficSource' => ['test']];
        }
        $client = new Client();
        $url = config('mortgage.str_host');

        Log::info('UW. getUserData. Params - ', [$url, $params]);

        try {
            $response = $client->post($url . '/api/newUser', [
                'body' => json_encode($params),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
            ]);
        } catch (GuzzleException $e) {
            $json = [];
            $httpStatusCode = Response::HTTP_BAD_REQUEST;
            if ($e instanceof RequestException) {
                if (!empty($e->getResponse())) {
                    $json = json_decode($e->getResponse()->getBody(), true);
                    $httpStatusCode = $e->getResponse()->getStatusCode();
                }
            }
            Log::error(__METHOD__ . '. Exception:', [$httpStatusCode, $json, $e->getMessage(), $e->getTraceAsString()]);
            return false;
        } catch (\Exception $e) {
            Log::error( __METHOD__ . '. ERROR - Response: ', [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            Log::error(__METHOD__ . '. ERROR - Response: ', [$response->getBody()]);
            return false;
        }
        $result = json_decode($response->getBody(), true);

        Log::info(__METHOD__ . '. Response', [$result]);


        return $result;
    }
}
