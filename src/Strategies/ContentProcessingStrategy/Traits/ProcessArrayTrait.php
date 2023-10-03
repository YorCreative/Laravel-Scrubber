<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Traits;

use YorCreative\Scrubber\Services\ScrubberService;

trait ProcessArrayTrait
{
    public function processArrayRecursively(array $content): array
    {
        foreach ($content as $key => $value) {
            if ($value !== null) {
                if (is_array($value)) {
                    $content[$key] = $this->processArray($value);
                } elseif (is_object($value) && ! method_exists($value, '__toString')) {
                    $content[$key] = $this->processArray((array) $value);
                } else {
                    $value = (string) $value;

                    ScrubberService::autoSanitize($value);

                    $content[$key] = $value;
                }
            }
        }

        return $content;
    }

    public function processArray(array $content): array
    {
        $jsonContent = ScrubberService::encodeRecord($content);
        if ($jsonContent === '') {
            // failed to convert array to JSON, so process array recursively
            return $this->processArrayRecursively($content);
        }

        ScrubberService::autoSanitize($jsonContent);

        return ScrubberService::decodeRecord($jsonContent);
    }
}
