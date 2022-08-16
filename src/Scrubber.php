<?php

namespace YorCreative\Scrubber;

use YorCreative\Scrubber\Services\ScrubberService;

class Scrubber
{
    /**
     * @param $content
     * @return array|string
     */
    public static function processMessage($content): array|string
    {
        return is_array($content)
            ? self::processArray($content)
            : self::processString($content);
    }

    /**
     * @param  array  $content
     * @return array
     */
    private static function processArray(array $content): array
    {
        $jsonContent = ScrubberService::encodeRecord($content);

        ScrubberService::autoSanitize($jsonContent);

        return ScrubberService::decodeRecord($jsonContent);
    }

    /**
     * @param $content
     * @return string
     */
    private static function processString($content): string
    {
        ScrubberService::autoSanitize($content);

        return $content;
    }
}
