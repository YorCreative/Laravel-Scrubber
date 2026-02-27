<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class Iban implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        // Matches IBAN: 2 letter country code + 2 check digits + up to 30 alphanumeric characters
        return '\b[A-Z]{2}\d{2}[A-Z0-9]{4,30}\b';
    }

    public function getTestableString(): string
    {
        // Test IBAN (GB format with zeros)
        return 'GB00TEST00000000000000';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): ?string
    {
        return '********************';
    }
}
