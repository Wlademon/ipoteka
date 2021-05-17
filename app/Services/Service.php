<?php

namespace App\Services;


abstract class Service
{
    protected $error = null;
    protected $errorCode;

    protected $pdfPath = 'ns/pdf/';

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
    protected function getErrorCode()
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
