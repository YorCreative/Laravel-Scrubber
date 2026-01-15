<?php

namespace YorCreative\Scrubber\Services;

use Carbon\Carbon;
use Monolog\LogRecord;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\SecretManager\Secret;

class ScrubberService
{
    /**
     * Track scrubbing statistics for the current request.
     *
     * @var array{total_scrubs: int, patterns_matched: array<string, int>}
     */
    protected static array $stats = [
        'total_scrubs' => 0,
        'patterns_matched' => [],
    ];

    public static function encodeRecord(mixed $record): string
    {
        if (is_array($record)) {
            $json = json_encode($record);

            return $json === false ? '' : $json;
        }

        // Ensure non-array inputs are strings
        return (string) $record;
    }

    public static function decodeRecord(mixed $scrubbedContent): mixed
    {
        if (! is_array($scrubbedContent)) {
            $scrubbedContent = json_decode($scrubbedContent, true);
        }

        // set datetime back to  DateTimeInterface for papertrail specifically.
        if (isset($scrubbedContent['datetime'])) {
            $datetime = match (true) {
                is_array($scrubbedContent['datetime']) => $scrubbedContent['datetime']['date'],
                $scrubbedContent instanceof LogRecord => Carbon::instance($scrubbedContent['datetime']),
                default => Carbon::parse($scrubbedContent['datetime'])
            };

            $scrubbedContent['datetime'] = $datetime;
        }

        return $scrubbedContent;
    }

    public static function autoSanitize(string &$jsonContent): void
    {
        $regexRepository = app(RegexRepository::class);
        $defaultRedaction = config('scrubber.redaction');

        foreach ($regexRepository->getRegexCollection() as $regexClass) {
            $pattern = $regexClass->isSecret()
                ? Secret::decrypt($regexClass->getPattern())
                : $regexClass->getPattern();

            $replace = method_exists($regexClass, 'getReplacementValue')
                ? $regexClass->getReplacementValue()
                : $defaultRedaction;

            $patternName = class_basename($regexClass);

            try {
                self::patternChecker($pattern, $jsonContent, $replace, $patternName);
            } catch (\Exception $e) {
                if (config('app.debug')) {
                    logger()->debug('Scrubber: regex pattern failed', [
                        'pattern' => $pattern,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    protected static function patternChecker(string $regexPattern, string &$jsonContent, string $replace, string $patternName = 'unknown'): void
    {
        $hits = 0;
        $result = RegexRepository::checkAndSanitize($regexPattern, $replace, $jsonContent, $hits);
        if (! is_null($result)) {
            $jsonContent = $result;
        }

        // Track statistics
        if ($hits > 0) {
            self::$stats['total_scrubs'] += $hits;
            if (! isset(self::$stats['patterns_matched'][$patternName])) {
                self::$stats['patterns_matched'][$patternName] = 0;
            }
            self::$stats['patterns_matched'][$patternName] += $hits;
        }
    }

    /**
     * Test content against all patterns without modifying stats.
     *
     * @return array{matched: bool, patterns: array<string, int>, scrubbed: string}
     */
    public static function testContent(string $content): array
    {
        $regexRepository = app(RegexRepository::class);
        $defaultRedaction = config('scrubber.redaction');
        $patterns = [];
        $scrubbed = $content;

        foreach ($regexRepository->getRegexCollection() as $regexClass) {
            $pattern = $regexClass->isSecret()
                ? Secret::decrypt($regexClass->getPattern())
                : $regexClass->getPattern();

            $replace = method_exists($regexClass, 'getReplacementValue')
                ? $regexClass->getReplacementValue()
                : $defaultRedaction;

            $patternName = class_basename($regexClass);

            try {
                $hits = RegexRepository::check($pattern, $scrubbed);
                if ($hits > 0) {
                    $patterns[$patternName] = $hits;
                    $scrubbed = RegexRepository::checkAndSanitize($pattern, $replace, $scrubbed);
                }
            } catch (\Exception $e) {
                // Skip invalid patterns in test mode
            }
        }

        return [
            'matched' => count($patterns) > 0,
            'patterns' => $patterns,
            'scrubbed' => $scrubbed,
        ];
    }

    /**
     * Get scrubbing statistics for the current request.
     *
     * @return array{total_scrubs: int, patterns_matched: array<string, int>}
     */
    public static function getStats(): array
    {
        return self::$stats;
    }

    /**
     * Reset scrubbing statistics.
     */
    public static function resetStats(): void
    {
        self::$stats = [
            'total_scrubs' => 0,
            'patterns_matched' => [],
        ];
    }

    public static function getRegexRepository(): RegexRepository
    {
        return app()->get(RegexRepository::class);
    }
}
