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
        $this->handlers = new Collection();
    }

    public function setHandler(ProcessHandlerContract $handler): void
    {
        $this->handlers->push($handler);
    }

    public function processContent(mixed $content): string|array|LogRecord
    {
        $index = $this->detectHandlerIndex($content);

        return is_null($index)
            ? throw new RuntimeException('Cannot process content: '.json_encode($content))
            : $this->getHandlers()->get($index)->processContent($content);
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
