<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class PhoneNumber implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        // Matches various phone formats:
        // +1 (555) 123-4567, 555-123-4567, (555) 123-4567, 5551234567, +1-555-123-4567
        return '(?:\+?1[-.\s]?)?(?:\(?\d{3}\)?[-.\s]?)?\d{3}[-.\s]?\d{4}\b';
    }

    public function getTestableString(): string
    {
        return '+1 (555) 000-0000';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): string
    {
        return '(***) ***-****';
    }
}
