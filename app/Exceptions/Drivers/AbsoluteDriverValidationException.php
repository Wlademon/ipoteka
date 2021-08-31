<?php

namespace App\Exceptions\Drivers;


use Illuminate\Validation\ValidationException;

/**
 * Class AbsoluteDriverValidationException
 *
 * @package App\Exceptions\Drivers
 */
class AbsoluteDriverValidationException extends DriverException
{
    /**
     * @param  string  $method
     * @param  array   $messages
     *
     * @return static
     */
    public static function withMessages(string $method, array $messages)
    {
        $exception = ValidationException::withMessages($messages);

        return new static($method, $exception->getMessage(), static::DEFAULT_CODE, $exception);
    }
}