<?php

namespace YorCreative\Scrubber\Handlers;

use Monolog\Handler\NullHandler;
use YorCreative\Scrubber\Scrubber;

class ScrubberTap
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            if (! ($handler instanceof NullHandler)) {
                $handler->pushProcessor(function ($record) {
                    return Scrubber::processMessage($record);
                });
            }
        }
    }
}
