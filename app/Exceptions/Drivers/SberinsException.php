<?php


namespace App\Exceptions\Drivers;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class SberinsException
 *
 * @package App\Exceptions\Drivers
 */
class SberinsException extends \Exception implements DriverExceptionInterface
{
    protected $code = Response::HTTP_NOT_ACCEPTABLE;
}
