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
                    $content[$key] = $this->processArrayRecursively($value);
                } elseif (is_object($value) && ! method_exists($value, '__toString')) {
                    $content[$key] = $this->processArrayRecursively((array) $value);
                } else {
                    $value = (string) $value;
                    try {
                        ScrubberService::autoSanitize($value);
                    } catch (\Exception $e) {
                        // Skip sanitization for this value to prevent breaking the array
                    }
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

        try {
            ScrubberService::autoSanitize($jsonContent);

        } catch (\Exception $e) {
            return $this->processArrayRecursively($content);
        }

        if (! is_string($jsonContent)) {
            return $this->processArrayRecursively($content);
        }

        $decoded = ScrubberService::decodeRecord($jsonContent);
        if ($decoded === null) {
            return $this->processArrayRecursively($content);
        }

        return $decoded;
    }
}
