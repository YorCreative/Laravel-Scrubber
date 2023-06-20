<?php

namespace YorCreative\Scrubber;

use Monolog\LogRecord;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ContentProcessingStrategy;

class Scrubber
{
    public static function processMessage($content): array|string|LogRecord
    {
        return app()->get(ContentProcessingStrategy::class)->processContent($content);
    }
}
