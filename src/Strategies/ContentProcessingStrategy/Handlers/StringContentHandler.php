<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers;

use Monolog\LogRecord;
use YorCreative\Scrubber\Services\ScrubberService;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ProcessHandlerContract;

class StringContentHandler implements ProcessHandlerContract
{
    public function canProcess(mixed $content): bool
    {
        return is_string($content);
    }

    public function processContent(mixed $content): string|array|LogRecord
    {
        ScrubberService::autoSanitize($content);

        return $content;
    }
}
