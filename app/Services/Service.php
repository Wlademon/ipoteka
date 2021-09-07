<?php

namespace App\Services;

/**
 * Class Service
 *
 * @package App\Services
 */
abstract class Service
{
    protected ?string $error = null;
    protected ?int $errorCode;

    protected string $pdfPath = 'ns/pdf/';

    /**
     * @return String|null
     */
    protected function getError()
    {
        return $this->error;
    }

    /**
     * @return Int|null
     */
    protected function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    /**
     * Create a new service instance.
     */
    public function __construct()
    {

    }
}
