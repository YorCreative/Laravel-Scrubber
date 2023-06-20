<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers;

use Monolog\Logger;
use Monolog\LogRecord;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ContentProcessingStrategy;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ProcessHandlerContract;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Traits\ProcessArrayTrait;

class LogRecordContentHandler implements ProcessHandlerContract
{
    use ProcessArrayTrait;

    public function canProcess(mixed $content): bool
    {
        return $content instanceof LogRecord;
    }

    public function processContent(mixed $content): string|array|LogRecord
    {
        $logRecordArr = $content->toArray();

        $logRecordArr['message'] = empty($message = $logRecordArr['message'])
            ? $message
            : app(ContentProcessingStrategy::class)->processContent($logRecordArr['message']);

        $logRecordArr['context'] = empty($context = $logRecordArr['context'])
            ? []
            : app(ContentProcessingStrategy::class)->processContent($context);

        $logRecord = new LogRecord(
            $logRecordArr['datetime'],
            $logRecordArr['channel'],
            Logger::toMonologLevel($logRecordArr['level']),
            $logRecordArr['message'],
            $logRecordArr['context'],
            $logRecordArr['extra']
        );

        return $logRecord;
    }
}
