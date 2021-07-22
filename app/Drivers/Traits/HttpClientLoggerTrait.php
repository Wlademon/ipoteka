<?php

namespace App\Drivers\Traits;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Log\LogManager;
use Throwable;

trait HttpClientLoggerTrait
{
    protected ?Closure $beforeSendFunc;
    protected ?Closure $afterSendFunc;
    protected ?Closure $errorSendFunc;
    protected ?Closure $alwaysSendFunc;
    protected LogManager $logManager;

    /**
     * @param $logManager LogManager
     */
    public function setLogManager(LogManager $logManager): self
    {
        $this->logManager = $logManager;

        return $this;
    }

    /**
     * @param $afterSendFunc Closure
     */
    public function setAfterSendFunc(Closure $afterSendFunc): self
    {
        $this->afterSendFunc = $afterSendFunc;

        return $this;
    }

    /**
     * @param $alwaysSendFunc Closure
     */
    public function setAlwaysSendFunc(Closure $alwaysSendFunc): self
    {
        $this->alwaysSendFunc = $alwaysSendFunc;

        return $this;
    }

    /**
     * @param $beforeSendFunc Closure
     */
    public function setBeforeSendFunc(Closure $beforeSendFunc): self
    {
        $this->beforeSendFunc = $beforeSendFunc;

        return $this;
    }

    /**
     * @param  Closure  $errorSendFunc
     *
     * @return $this
     */
    public function setErrorSendFunc(Closure $errorSendFunc): self
    {
        $this->errorSendFunc = $errorSendFunc;

        return $this;
    }

    /**
     * @return $this
     */
    public function freshSendFuncs(): self
    {
        $this->alwaysSendFunc = $this->afterSendFunc = $this->errorSendFunc = $this->beforeSendFunc = null;

        return $this;
    }

    public function defBeforeSendFunc(
        string $method,
        string $message = '',
        array $params = []
    ): ?Closure {
        $manager = $this->logManager;
        if (!$manager) {
            return null;
        }

        return function (Request $request) use ($manager, $message, $method, $params)
        {
            $manager->info(
                $method . ($message ? " $message" : ''),
                array_merge(
                    [
                        'url' => $request->url(),
                        'body' => $request->body(),
                        'headers' => $request->headers(),
                    ],
                    $params
                )
            );
        };
    }

    public function defAfterSendFunc(
        string $method,
        string $message = '',
        array $params = []
    ): ?Closure {
        $manager = $this->logManager;
        if (!$manager) {
            return null;
        }

        return function (Response $response) use ($manager, $message, $method, $params)
        {
            $manager->info(
                $method . ($message ? " $message" : ''),
                array_merge(
                    [
                        'request_url' => $response->url(),
                        'request_body' => $response->body(),
                        'request_headers' => $response->headers(),
                    ],
                    $params
                )
            );
        };
    }

    public function defErrorSendFunc(string $method, string $message = '', array $params = [])
    {
        $manager = $this->logManager;
        if (!$manager) {
            return null;
        }

        return function (Throwable $exception) use ($manager, $message, $method, $params)
        {
            $context = array_merge(
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ],
                $params
            );
            if ($exception instanceof RequestException) {
                array_merge(
                    $context,
                    [
                        'response_url' => $exception->response->url(),
                        'response_body' => $exception->response->body(),
                        'response_headers' => $exception->response->headers(),
                    ]
                );
            }

            $manager->error(
                $method . ($message ? " $message" : ''),
                $context
            );
        };
    }
}