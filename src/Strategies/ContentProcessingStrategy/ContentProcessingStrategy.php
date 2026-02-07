<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy;

use Illuminate\Support\Collection;
use Monolog\LogRecord;
use RuntimeException;

class ContentProcessingStrategy
{
    protected Collection $handlers;

    public function __construct()
    {
        $this->handlers = new Collection;
    }

    public function setHandler(ProcessHandlerContract $handler): void
    {
        $this->handlers->push($handler);
    }

    public function processContent(mixed $content): string|array|LogRecord
    {
        $index = $this->detectHandlerIndex($content);

        $handler = $index === null ? null : $this->getHandlers()->get($index);
        if ($handler === null) {
            throw new RuntimeException('Cannot process content of type: '.gettype($content));
        }

        return $handler->processContent($content);
    }

    private function detectHandlerIndex(mixed $content): ?int
    {
        return $this->getHandlers()->search(function (ProcessHandlerContract $handler) use ($content) {
            return $handler->canProcess($content);
        });
    }

    public function getHandlers(): Collection
    {
        return $this->handlers;
    }
}
