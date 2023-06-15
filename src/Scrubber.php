<?php

namespace YorCreative\Scrubber;

use Carbon\Carbon;
use Monolog\Logger;
use Monolog\LogRecord;
use YorCreative\Scrubber\Services\ScrubberService;

class Scrubber
{
    public static function processMessage($content): array|string|LogRecord
    {
        $normalizeContent = fn($contentArg) => match (true) {
            $contentArg instanceof LogRecord => $contentArg->toArray(),
            default => $contentArg
        };

        $processMessage = fn($contentArg) => match (true) {
            is_array($contentArg) => self::processArray($contentArg),
            default => self::processString($contentArg)
        };

        $isInstantiableToLogRecord = fn($contentArg) => is_array($contentArg) && count(array_intersect([
                "message",
                "context",
                "level",
                "class",
                "channel",
                "datetime",
                "extra"
            ],
                array_keys($contentArg)
            )) === 6;

        $recordFactory = fn($logRecordArray) => new LogRecord(
            match (true) {
                $logRecordArray['datetime'] instanceof Carbon => $logRecordArray['datetime']->toDateTimeImmutable(),
                is_string($logRecordArray['datetime']) => Carbon::parse($logRecordArray['datetime'])->toDateTimeImmutable(),
                default => $logRecordArray['datetime'],
            },
            $logRecordArray['channel'],
            Logger::toMonologLevel($logRecordArray['level']),
            $logRecordArray['message'],
            $logRecordArray['context'],
            $logRecordArray['extra'],
            $logRecordArray['class'] ?? null
        );

        $normalizeOutput = fn($contentArg) => match (true) {
            $isInstantiableToLogRecord($contentArg) => $recordFactory($contentArg),
            default => $contentArg
        };

        $processMessagePipeline = [
            $normalizeContent,
            $processMessage,
            $normalizeOutput
        ];

        $processMessagePipeline = fn($contentArg) => array_reduce($processMessagePipeline, fn($carry, \Closure $pipelineJob) => $pipelineJob($carry), $contentArg);

        return $processMessagePipeline($content);
    }

    private static function processArray(array $content): array
    {
        $jsonContent = ScrubberService::encodeRecord($content);
        if ('' === $jsonContent) {
            // failed to convert array to JSON, so process array recursively
            return self::processArrayRecursively($content);
        }

        ScrubberService::autoSanitize($jsonContent);

        return ScrubberService::decodeRecord($jsonContent);
    }

    private static function processArrayRecursively(array $content): array
    {
        foreach ($content as $key => $value) {
            if (null !== $value) {
                if (is_array($value)) {
                    $content[$key] = self::processArray($value);
                } elseif (is_object($value) && ! method_exists($value, '__toString')) {
                    $content[$key] = self::processArray((array) $value);
                } else {
                    $content[$key] = self::processString((string) $value);
                }
            }
        }

        return $content;
    }

    private static function processString($content): string
    {
        ScrubberService::autoSanitize($content);

        return $content;
    }
}
