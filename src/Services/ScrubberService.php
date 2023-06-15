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
            return json_encode($record);
        } else {
            return $record;
        }
    }

    public static function decodeRecord($scrubbedContent): mixed
    {
        if (!is_array($scrubbedContent)) {
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

            self::patternChecker($pattern, $jsonContent);
        });
    }

    protected static function patternChecker(string $regexPattern, string &$jsonContent): void
    {
        $hits = 0;
        $jsonContent = RegexRepository::checkAndSanitize($regexPattern, $jsonContent, $hits);

        /**
         * @todo
         * add detection reporting
         *
         **/
    }
}
