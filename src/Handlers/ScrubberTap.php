<?php

namespace YorCreative\Scrubber\Handlers;

use Monolog\Handler\NullHandler;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Services\ScrubberService;

class ScrubberTap
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            if (! ($handler instanceof NullHandler)) {
                $handler->pushProcessor(function ($record) {
                    ScrubberService::setContext('log');
                    try {
                        return Scrubber::processMessage($record);
                    } finally {
                        ScrubberService::setContext('manual');
                    }
                });
            }
        }
    }
}
