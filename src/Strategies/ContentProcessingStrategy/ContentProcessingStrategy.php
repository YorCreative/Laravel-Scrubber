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

    /**
     * @param ProcessHandlerContract $handler
     */
    public function setHandler(ProcessHandlerContract $handler): void
    {
        $this->handlers->push($handler);
    }

    /**
     * @param mixed $content
     * @return string|array|LogRecord
     */
    public function processContent(mixed $content): string|array|LogRecord
    {
        $index = $this->detectHandlerIndex($content);

        return is_null($index)
            ? throw new RuntimeException('Cannot process content: ' . json_encode($content))
            : $this->getHandlers()->get($index)->processContent($content);
    }

    /**
     * @param mixed $content
     * @return int|null
     */
    private function detectHandlerIndex(mixed $content): ?int
    {
        return $this->getHandlers()->search(function (ProcessHandlerContract $handler) use ($content) {
            return $handler->canProcess($content);
        });
    }

    /**
     * @return Collection
     */
    public function getHandlers(): Collection
    {
        return $this->handlers;
    }
}
