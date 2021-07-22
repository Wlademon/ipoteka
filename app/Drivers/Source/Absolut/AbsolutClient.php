<?php

namespace App\Drivers\Source\Absolut;

use App\Drivers\Traits\HttpClientLoggerTrait;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AbsolutClient
{
    use HttpClientLoggerTrait;

    const TOKEN_URL = '/oauth/token';
    protected string $host;
    protected string $clientId;
    protected string $clientSecret;
    protected ?Repository $cacheManager;

    public function __construct(
        string $host,
        string $clientId,
        string $clientSecret,
        ?Repository $manager = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cacheManager = $manager;
        $this->host = $host;
    }

    protected function getRequest()
    {
        return Http::baseUrl($this->host);
    }

    protected function getToken(): array
    {
        if ($this->cacheManager) {
            $key = $this->getCacheKey(__METHOD__);
            if ($this->cacheManager->has($key)) {
                return $this->cacheManager->get($key);
            }
        }

        $request = $this->getRequest()->withBasicAuth($this->clientId, $this->clientSecret)->asForm();

        $this->freshSendFuncs()
            ->setBeforeSendFunc(
                $this->defBeforeSendFunc(__METHOD__, 'Получение токена')
            )
            ->setAfterSendFunc($this->defAfterSendFunc(__METHOD__, 'Токен получен'))
            ->setErrorSendFunc($this->defErrorSendFunc(__METHOD__, 'Ошибка получения токена'));

        $response = $this->request(
            $request,
            function (PendingRequest $request, array $data = [])
            {
                return $request->post(self::TOKEN_URL, $data);
            },
            ['grant_type' => 'client_credentials']
        );

        $responseData = $response->json();

        if ($this->cacheManager) {
            $this->cacheManager->set(
                $key,
                \Arr::only($responseData, ['access_token', 'token_type']),
                \Arr::get($responseData, 'expires_in', 60) - 60
            );
        }

        return \Arr::only($responseData, ['access_token', 'token_type']);
    }

    public function request(
        PendingRequest $request,
        \Closure $sendFunction,
        array $data = []
    ): Response {
        try {
            if ($this->beforeSendFunc) {
                $request->beforeSending($sendFunction);
            }
            /** @var Response $response */
            $response = $sendFunction($request, $data);
        } catch (Throwable $throwable) {
            if ($this->errorSendFunc) {
                ($this->afterSendFunc)($throwable, $request, $data);
            }
            throw $throwable;
        } finally {
            if ($this->alwaysSendFunc) {
                ($this->alwaysSendFunc)($request, $data);
            }
        }
        if ($this->afterSendFunc) {
            ($this->afterSendFunc)($response, $request, $data);
        }

        return $response;
    }

    protected function getCacheKey(string $prefix = '')
    {
        $data = [
            $this->host,
            $this->clientId,
            $this->clientSecret,
        ];

        return $prefix . sha1(json_encode($data));
    }
}