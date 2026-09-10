<?php

namespace YorCreative\Scrubber\Tests\Fixtures;

use JsonSerializable;

/**
 * A DTO whose JSON contract deliberately omits a secret it holds internally.
 */
class SecretiveDto implements JsonSerializable
{
    public function __construct(
        public string $name,
        private string $password
    ) {}

    public function jsonSerialize(): mixed
    {
        return ['name' => $this->name];
    }
}
