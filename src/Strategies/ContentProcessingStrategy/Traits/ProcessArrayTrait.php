<?php

namespace YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Traits;

use YorCreative\Scrubber\Services\ScrubberService;

trait ProcessArrayTrait
{
    /**
     * Depth ceiling for the encoding-failure fallback. json_encode() refuses
     * self-referential structures outright; this stops the reconstruction from
     * following one forever.
     */
    private const MAX_FALLBACK_DEPTH = 32;

    /**
     * Redact structured content by sanitizing each decoded value in place.
     *
     * Array content used to be JSON-encoded, matched as a single blob and decoded
     * again. Patterns are written against plaintext, but JSON encoding changes the
     * bytes they see, and that broke redaction in two ways:
     *
     *  - a literal backslash inside a secret is doubled by json_encode, so the
     *    quoted plaintext pattern for that secret no longer matched; and
     *  - a leading newline, tab or carriage return becomes the two characters
     *    \n, \t or \r, which puts the word characters n, t and r immediately
     *    before the credential and defeats the (?<![A-Za-z0-9_]) left boundary
     *    that every provider pattern relies on to avoid lookalikes.
     *
     * Encoding also made redaction itself unsafe: replacing a bare JSON number
     * with the redaction marker produced invalid JSON, json_decode() then failed,
     * and the fallback path stringified every scalar in the record as a side
     * effect. Working on decoded values removes the whole class of problem --
     * there is no encoded form to get out of step with the patterns.
     *
     * Objects are still expanded through their JSON representation rather than a
     * raw (array) cast, so the serialization contract a caller relies on is the
     * one that reaches the log. See decodeObject().
     */
    public function processArray(array $content): array
    {
        return $this->processArrayRecursively($content);
    }

    public function processArrayRecursively(array $content): array
    {
        $result = [];

        foreach ($content as $key => $value) {
            // Keys are redacted too: the previous whole-blob pass matched them
            // because they were part of the JSON text, and callers rely on a
            // credential used as a key being scrubbed like any other value.
            // Integer keys are included -- PHP silently casts a numeric-string
            // key such as a card number to an integer, so skipping them would
            // let exactly the values most worth redacting through untouched.
            $newKey = $this->sanitizeKey($key);

            if ($value === null) {
                $result[$newKey] = null;

                continue;
            }

            if (is_array($value)) {
                $result[$newKey] = $this->processArrayRecursively($value);

                continue;
            }

            if (is_object($value)) {
                $decoded = $this->decodeObject($value);

                if ($decoded === null) {
                    $result[$newKey] = null;
                } elseif (is_array($decoded)) {
                    $result[$newKey] = $this->processArrayRecursively($decoded);
                } else {
                    $result[$newKey] = $this->sanitizeLeafValue($decoded, $key);
                }

                continue;
            }

            $result[$newKey] = $this->sanitizeLeafValue($value, $key);
        }

        return $result;
    }

    /**
     * Expand an object the way json_encode() would, not the way (array) would.
     *
     * A raw (array) cast ignores JsonSerializable and drags in private and
     * protected state under null-byte-mangled keys, so a DTO that deliberately
     * publishes only a subset of itself would have the rest of it written to the
     * log. Round-tripping through JSON keeps every serialization contract the
     * previous whole-blob implementation honoured: jsonSerialize() decides the
     * shape when implemented, Eloquent models and other Arrayable objects expand
     * to their public representation as nested arrays rather than collapsing to
     * a string, and non-public properties never appear.
     *
     * The round trip is safe for redaction because the decoded values are what
     * gets scanned -- the escaping that broke the old blob pass is undone before
     * any pattern sees the text.
     */
    private function decodeObject(object $value): mixed
    {
        $encoded = json_encode($value);

        if ($encoded === false) {
            // Unencodable: binary (non-UTF-8) values, recursion, unsupported
            // types. Rebuild the representation json_encode() *would* have
            // produced rather than reaching for the object's raw state --
            // see unencodableRepresentationOf(). JSON_PARTIAL_OUTPUT_ON_ERROR
            // is deliberately not used: it would silently replace a binary
            // value with null.
            return $this->unencodableRepresentationOf($value);
        }

        return json_decode($encoded, true);
    }

    /**
     * Reconstruct what json_encode() would have produced, for content it cannot
     * encode.
     *
     * Every serialization contract is honoured on the way down. A JsonSerializable
     * object is replaced by whatever jsonSerialize() returns -- including on this
     * failure path, where it matters most: an object whose serializer withholds a
     * public property still has that property withheld, instead of the fallback
     * publishing it because the encode happened to fail on some unrelated binary
     * byte. Only an object with no JSON contract of its own is described by its
     * public properties, which is exactly what json_encode() does with it.
     */
    private function unencodableRepresentationOf(mixed $value, int $depth = 0): mixed
    {
        if ($depth > self::MAX_FALLBACK_DEPTH) {
            return null;
        }

        if ($value instanceof \JsonSerializable) {
            // jsonSerialize() may itself hand back objects, so keep resolving.
            return $this->unencodableRepresentationOf($value->jsonSerialize(), $depth + 1);
        }

        if (is_object($value)) {
            $value = $this->publicPropertiesOf($value);
        }

        if (is_array($value)) {
            $representation = [];

            foreach ($value as $key => $item) {
                $representation[$key] = is_object($item) || is_array($item)
                    ? $this->unencodableRepresentationOf($item, $depth + 1)
                    : $item;
            }

            return $representation;
        }

        return $value;
    }

    /**
     * get_object_vars() honours calling scope, so a static closure bound to no
     * class sees only the public properties of whatever object it is given.
     */
    private function publicPropertiesOf(object $value): array
    {
        $reader = \Closure::bind(
            static fn (object $object): array => get_object_vars($object),
            null,
            null
        );

        return $reader($value);
    }

    /**
     * Sanitize a leaf value, preserving its scalar type when nothing was redacted.
     *
     * Numbers and booleans are still scanned -- a card number or account id may
     * well be stored as an int, and skipping non-strings would silently stop
     * redacting it. Only an untouched value keeps its original type; once a value
     * has been redacted it necessarily becomes a string, because the redaction
     * marker is one.
     */
    private function sanitizeLeafValue(mixed $value, int|string $key): mixed
    {
        $sanitized = (string) $value;
        $original = $sanitized;

        try {
            ScrubberService::autoSanitize($sanitized);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                logger()->debug('Scrubber: failed to sanitize value', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }

            return $value;
        }

        // Only true scalars keep their original type. Anything else that reached
        // here -- a resource, or a JSON scalar produced by an object -- has always
        // been recorded in its string form, so it stays a string.
        if ($sanitized === $original && (is_int($value) || is_float($value) || is_bool($value))) {
            return $value;
        }

        return $sanitized;
    }

    /**
     * Sanitize an array key, keeping its original type when nothing was redacted.
     *
     * A redacted key is necessarily a string, because the redaction marker is one.
     */
    private function sanitizeKey(int|string $key): int|string
    {
        $sanitized = (string) $key;
        $original = $sanitized;

        try {
            ScrubberService::autoSanitize($sanitized);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                logger()->debug('Scrubber: failed to sanitize key', [
                    'error' => $e->getMessage(),
                ]);
            }

            return $key;
        }

        return $sanitized === $original ? $key : $sanitized;
    }
}
