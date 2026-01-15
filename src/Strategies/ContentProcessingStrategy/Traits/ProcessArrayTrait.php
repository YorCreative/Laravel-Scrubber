<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Traits;

use YorCreative\Scrubber\Services\ScrubberService;

trait ProcessArrayTrait
{
    public function processArrayRecursively(array $content): array
    {
        foreach ($content as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $content[$key] = $this->processArrayRecursively($value);
            } elseif (is_object($value) && ! method_exists($value, '__toString')) {
                $content[$key] = $this->processArrayRecursively((array) $value);
            } else {
                $stringValue = (string) $value;
                try {
                    ScrubberService::autoSanitize($stringValue);
                    $content[$key] = $stringValue;
                } catch (\Exception $e) {
                    if (config('app.debug')) {
                        logger()->debug('Scrubber: failed to sanitize value', [
                            'key' => $key,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $content;
    }

    public function processArray(array $content): array
    {
        $jsonContent = ScrubberService::encodeRecord($content);

        // Failed to convert array to JSON, process recursively
        if ($jsonContent === '') {
            return $this->processArrayRecursively($content);
        }

        try {
            ScrubberService::autoSanitize($jsonContent);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                logger()->debug('Scrubber: array sanitization failed, falling back to recursive', [
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->processArrayRecursively($content);
        }

        $decoded = ScrubberService::decodeRecord($jsonContent);

        // If decoding failed, fall back to recursive processing
        if (! is_array($decoded)) {
            return $this->processArrayRecursively($content);
        }

        return $decoded;
    }
}
