<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class SocialSecurityNumber implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        // Matches SSN formats: 123-45-6789, 123 45 6789, 123456789
        return '\b(?!000|666|9\d{2})\d{3}[-\s]?(?!00)\d{2}[-\s]?(?!0000)\d{4}\b';
    }

    public function getTestableString(): string
    {
        // Using obviously fake SSN for tests - 123-45-6789 is a well-known test value
        // This matches the regex pattern but is universally recognized as fake
        return '123-45-6789';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): ?string
    {
        return '***-**-****';
    }
}
