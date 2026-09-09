<?php

namespace YorCreative\Scrubber\Tests\Fixtures;

use JsonSerializable;

/**
 * A DTO whose JSON contract excludes a public property, and whose serialized
 * payload contains a byte sequence json_encode() cannot represent.
 *
 * This is the failure path: json_encode() rejects the invalid UTF-8, so any
 * fallback that reaches for the object's raw properties publishes the very
 * field jsonSerialize() withheld.
 */
class BinaryPayloadDto implements JsonSerializable
{
    public string $password = 'excluded-public-password';

    public function jsonSerialize(): mixed
    {
        return ['payload' => "\xB1"];
    }
}
