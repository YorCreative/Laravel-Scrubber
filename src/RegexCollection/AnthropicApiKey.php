<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class AnthropicApiKey implements RegexCollectionInterface
{
    /**
     * Anthropic keys carry an "ant" vendor segment (sk-ant-api03-, sk-ant-admin01-),
     * which keeps them distinct from the OpenAI sk-proj-/legacy sk- shapes.
     */
    public function getPattern(): string
    {
        return '(?<![A-Za-z0-9_])sk-ant-(?:api|admin)[0-9]{2}-[A-Za-z0-9_-]{20,}';
    }

    public function getTestableString(): string
    {
        return 'sk-ant-api03-Kq7ZmR4tV2wXbN9yLcE6dPfGhJ1sAuI3oB5rT8yUqMxVnCgKlDeShFa-QwErTyU';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): ?string
    {
        return null;
    }
}
