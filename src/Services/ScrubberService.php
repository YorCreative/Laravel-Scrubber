<?php

namespace YorCreative\Scrubber\Services;

use Carbon\Carbon;
use Monolog\LogRecord;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\SecretManager\Secret;

class ScrubberService
{
    public static function encodeRecord($record): string
    {
        if (is_array($record)) {
            $json = json_encode($record);

            return $json === false ? '' : $json;
        }

        // Ensure non-array inputs are strings
        return (string) $record;
    }

    public static function decodeRecord($scrubbedContent): mixed
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
        app(RegexRepository::class)->getRegexCollection()->each(function (RegexCollectionInterface $regexClass) use (&$jsonContent) {
            $pattern = $regexClass->isSecret()
                ? Secret::decrypt($regexClass->getPattern())
                : $regexClass->getPattern();

            $replace = method_exists($regexClass, 'getReplacementValue')
                ? $regexClass->getReplacementValue()
                : config('scrubber.redaction');

            try {
                self::patternChecker($pattern, $jsonContent, $replace);
            } catch (\Exception $e) {
                // Skip this regex $pattern to prevent breaking the autoSanitizer loop.
            }
        });
    }

    protected static function patternChecker(string $regexPattern, string &$jsonContent, string $replace): void
    {
        $hits = 0;
        $result = RegexRepository::checkAndSanitize($regexPattern, $replace, $jsonContent, $hits);
        if (! is_null($result)) {
            $jsonContent = $result;
        }

        /**
         * @todo
         * add detection reporting
         *
         **/
    }

    public static function getRegexRepository(): RegexRepository
    {
        return app()->get(RegexRepository::class);
    }
}
