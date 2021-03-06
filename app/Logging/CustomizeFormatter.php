<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Request;

class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
//            $sessionId = Request::session()->getId();
            $sessionId = Request::header('X-SID'); // X-SID - UUID формирует плагин nabu
            $isDebug = env('APP_DEBUG');
            $formatter = new LineFormatter("[%datetime%] [{$sessionId}] %channel%.%level_name%: %message% %context% %extra%\n", 'Y-m-d H:i:s.u', true, true);
            $formatter->includeStacktraces($isDebug);
            $handler->setFormatter($formatter);
        }
    }
}
