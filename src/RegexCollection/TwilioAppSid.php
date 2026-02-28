<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class TwilioAppSid implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'AP[a-zA-Z0-9_\-]{32}';
    }

    public function getTestableString(): string
    {
        return 'APrNR8EQ0O8QYoWu2RuIS0X1S1ngjNI7s9';
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
