<?php

namespace YorCreative\Scrubber\Handlers;

use YorCreative\Scrubber\Scrubber;

class ScrubberTap
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
                return Scrubber::processMessage($record);
            });
        }
    }
}
