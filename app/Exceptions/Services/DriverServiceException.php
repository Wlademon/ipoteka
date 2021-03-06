<?php
declare(strict_types = 1);

namespace App\Exceptions\Services;

use Log;
use Throwable;

/**
 * Class DriverServiceException
 *
 * @package App\Exceptions\Services
 */
class DriverServiceException extends \Exception implements ServiceExceptionInterface, LogExceptionInterface
{
    public ?string $method;
    public ?string $o_message;
    public ?string $o_code;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function addLogData(
        string $method,
        ?string $originalMessage = null,
        ?string $originalCode = null
    ): LogExceptionInterface {
        $this->method = $method;
        $this->o_message = $originalMessage;
        $this->o_code = $originalCode;

        return $this;
    }

    public function log(): void
    {
        $message = '';
        if ($this->method) {
            $message .= $this->method;
        }
        if ($this->o_message) {
            $message .= " {$this->o_message}";
            if ($this->o_code) {
                $message .= " (code: {$this->o_code})";
            } else {
                $message .= " (code: {$this->getCode()})";
            }
        } else {
            $message .= " {$this->getMessage()} (code: {$this->getCode()})";
        }

        Log::error($message, ['trace' => $this->getTraceAsString()]);
    }
}
