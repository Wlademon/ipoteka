<?php

namespace App\Exceptions\Services;

use Throwable;

/**
 * Interface LogExceptionInterface
 *
 * @package App\Exceptions\Services
 */
interface LogExceptionInterface extends Throwable
{
    public function addLogData(
        string $method,
        ?string $originalMessage = null,
        ?string $originalCode = null
    ): LogExceptionInterface;

    public function log(): void;
}
