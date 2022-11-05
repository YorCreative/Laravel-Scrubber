<?php

namespace YorCreative\Scrubber\Services;

use Carbon\Carbon;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\SecretManager\Secret;

class ScrubberService
{
    /**
     * @param $record
     * @return string
     */
    public static function encodeRecord($record): string
    {
        if (is_array($record)) {
            return json_encode($record);
        } else {
            return $record;
        }
    }

    /**
     * @param $scrubbedContent
     * @return mixed
     */
    public static function decodeRecord($scrubbedContent): mixed
    {
        if (! is_array($scrubbedContent)) {
            $scrubbedContent = json_decode($scrubbedContent, true);
        }

        // set datetime back to  DateTimeInterface for papertrail specifically.
        if (isset($scrubbedContent['datetime'])) {
            $scrubbedContent['datetime'] = Carbon::parse($scrubbedContent['datetime']);
        }

        return $scrubbedContent;
    }

    /**
     * @param  string  $jsonContent
     * @return void
     */
    public static function autoSanitize(string &$jsonContent): void
    {
        app(RegexRepository::class)->getRegexCollection()->each(function (RegexCollectionInterface $regexClass) use (&$jsonContent) {
            $pattern = $regexClass->isSecret()
                ? Secret::decrypt($regexClass->getPattern())
                : $regexClass->getPattern();

            self::patternChecker($pattern, $jsonContent);
        });
    }

    /**
     * @param  string  $regexPattern
     * @param  string  $jsonContent
     */
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
