<?php

namespace App\Exceptions\Services;

use Throwable;

interface LogExceptionInterface extends Throwable
{
    public function addLogData(
        string $method,
        ?string $originalMessage = null,
        ?string $originalCode = null
    ): LogExceptionInterface;

    public function log(): void;
}
