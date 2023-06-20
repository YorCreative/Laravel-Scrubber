<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers;

use Monolog\LogRecord;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ProcessHandlerContract;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Traits\ProcessArrayTrait;

class ArrayContentHandler implements ProcessHandlerContract
{
    use ProcessArrayTrait;

    public function canProcess(mixed $content): bool
    {
        return is_array($content);
    }

    public function processContent(mixed $content): string|array|LogRecord
    {
        return $this->processArray($content);
    }
}
