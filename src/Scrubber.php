<?php

namespace YorCreative\Scrubber;

use Monolog\LogRecord;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Services\ScrubberService;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ContentProcessingStrategy;

class Scrubber
{
    public static function processMessage(mixed $content): array|string|LogRecord
    {
        return app()->get(ContentProcessingStrategy::class)->processContent($content);
    }

    public static function getRegexRepository(): RegexRepository
    {
        return ScrubberService::getRegexRepository();
    }

    /**
     * Test a string against all loaded regex patterns and return details about what would be scrubbed.
     *
     * @return array{matched: bool, patterns: array<string, int>, scrubbed: string}
     */
    public static function test(string $content): array
    {
        return ScrubberService::testContent($content);
    }

    /**
     * Get statistics about scrubbing operations in the current request.
     *
     * @return array{total_scrubs: int, patterns_matched: array<string, int>}
     */
    public static function getStats(): array
    {
        return ScrubberService::getStats();
    }

    /**
     * Reset the scrubbing statistics counter.
     */
    public static function resetStats(): void
    {
        ScrubberService::resetStats();
    }
}
