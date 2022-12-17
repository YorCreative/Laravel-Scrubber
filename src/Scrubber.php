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
        if ('' === $jsonContent) {
            // failed to convert array to JSON, so process array recursively
            return self::processArrayRecursively($content);
        }

        ScrubberService::autoSanitize($jsonContent);

        return ScrubberService::decodeRecord($jsonContent);
    }

    /**
     * @param  array  $content
     * @return array
     */
    private static function processArrayRecursively(array $content): array
    {
        foreach ($content as $key => $value) {
            if (null !== $value) {
                if (is_array($value)) {
                    $content[$key] = self::processArrayRecursively($value);
                } else {
                    $content[$key] = self::processString($value);
                }
            }
        }

        return $content;
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
