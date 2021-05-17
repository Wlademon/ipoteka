<?php

namespace App\Drivers\Traits;

use RuntimeException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Trait LoggerTrait
 * @package App\Drivers\Traits
 */
trait LoggerTrait
{
    /**
     * @param $message
     * @throws \Exception
     */
    protected static function abortLog($message, string $exceptionClass = null, int $code = Response::HTTP_BAD_REQUEST): void
    {
        static::error($message);
        if (!$exceptionClass) {
            throw new RuntimeException($message, $code);
        }

        throw new $exceptionClass($message, $code);
    }

    /**
     * @param $message
     */
    protected static function log($message, array $context = []): void
    {
        $class = static::class;
        $debug = self::eachToCaller();
        $method = '';
        if ($debug) {
            $class = $debug['class'];
            $method = $debug['function'];
        }
        Log::info($class . '::' . $method . ' => ' . $message, $context);
    }

    protected static function error($message, array $context = []): void
    {
        $class = static::class;
        $debug = self::eachToCaller();
        $method = '';
        if ($debug) {
            $class = $debug['class'];
            $method = $debug['function'];
        }
        Log::error($class . '::' . $method . ' => ' . $message, $context);
    }

    protected static function warning($message, array $context = []): void
    {
        $class = static::class;
        $debug = self::eachToCaller();
        $method = '';
        if ($debug) {
            $class = $debug['class'];
            $method = $debug['function'];
        }
        Log::warning($class . '::' . $method . ' => ' . $message, $context);
    }

    /**
     * @return mixed|null
     */
    protected static function eachToCaller(): ?array
    {
        foreach (debug_backtrace() as $item) {
            if (!in_array($item['function'], ['warning', 'abortLog', 'log', 'eachToCaller'])) {
                return $item;
            }
        }

        return null;
    }
}
