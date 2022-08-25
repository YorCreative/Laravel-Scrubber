<?php

namespace YorCreative\Scrubber\Services;

use YorCreative\Scrubber\RegexCollectionInterface;
use YorCreative\Scrubber\Repositories\RegexRepository;

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
            return json_decode($scrubbedContent, true);
        } else {
            return $scrubbedContent;
        }
    }

    /**
     * @param  string  $jsonContent
     * @return void
     */
    public static function autoSanitize(string &$jsonContent): void
    {
        RegexRepository::getRegexCollection()->each(function (RegexCollectionInterface $regexClass) use (&$jsonContent) {
            self::patternChecker($regexClass->getPattern(), $jsonContent);
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
