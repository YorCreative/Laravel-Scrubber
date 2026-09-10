<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class OpenAiApiKey implements RegexCollectionInterface
{
    /**
     * OpenAI keys use a hyphen after the "sk" prefix (sk-proj-, sk-svcacct-,
     * sk-admin-, or the legacy sk-<48 chars>). Stripe uses an underscore
     * (sk_live_, sk_test_), so the delimiter alone keeps the two apart.
     */
    public function getPattern(): string
    {
        return '(?<![A-Za-z0-9_])(?:sk-(?:proj|svcacct|admin)-[A-Za-z0-9_-]{20,}|sk-[A-Za-z0-9]{32,})';
    }

    public function getTestableString(): string
    {
        return 'sk-proj-9TjLmQ0aXbR2vN6wYcE4dPfGhK1sZuA7oI3rB5tW8yUqMxVnCgJlDeSh';
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
