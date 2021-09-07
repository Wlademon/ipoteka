<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Request;

/**
 * Class CustomizeFormatter
 *
 * @package App\Logging
 */
class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  Logger  $logger
     * @return void
     */
    public function __invoke(Logger $logger)
    {
        foreach ($logger->getHandlers() as $handler) {
//            $sessionId = Request::session()->getId();
            $sessionId = Request::header('X-SID'); // X-SID - UUID формирует плагин nabu
            $isDebug = config('app.debug');
            $formatter = new LineFormatter("[%datetime%] [{$sessionId}] %channel%.%level_name%: %message% %context% %extra%\n", 'Y-m-d H:i:s.u', true, true);
            $formatter->includeStacktraces($isDebug);
            $handler->setFormatter($formatter);
        }
    }
}
