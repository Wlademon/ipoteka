<?php

namespace App\Exceptions\Drivers;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class DriverException
 *
 * @package App\Exceptions\Drivers
 */
abstract class DriverException extends Exception implements DriverExceptionInterface
{
    public const DEFAULT_CODE = 0;

    protected $code = Response::HTTP_NOT_ACCEPTABLE;

    public function __construct(
        string $method,
        string $message = "",
        int $code = self::DEFAULT_CODE,
        Throwable $previous = null
    ) {
        if ($code === 0) {
            $code = $this->code;
        }
        $this->log($previous, $method . ($message ? ' ' . $message : ''));
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param  Throwable|null  $exception
     * @param  string          $message
     */
    protected function log(?Throwable $exception, string $message = '')
    {
        $context = [];
        if (config('app.debug')) {
            $context['file'] = $this->getFile();
            $context['line'] = $this->getLine();
            $context['trace'] = $this->getTraceAsString();
        }
        if ($exception instanceof RequestException) {
            $context['request'] = $exception->getRequest();
            $context['response'] = $exception->hasResponse() ? $exception->getResponse() : null;
        }
        if ($exception instanceof ValidationException) {
            $context['errors'] = $exception->errors();
        }

        Log::error($message, $context);
    }
}