<?php

namespace App\Exceptions\Drivers;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AlphaException
 *
 * @package App\Exceptions\Drivers
 */
class AlphaException extends Exception implements DriverExceptionInterface
{
    protected $code = Response::HTTP_NOT_ACCEPTABLE;
}
