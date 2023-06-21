<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy;

use Monolog\LogRecord;

interface ProcessHandlerContract
{
    public function canProcess(mixed $content): bool;

    public function processContent(mixed $content): string|array|LogRecord;
}
