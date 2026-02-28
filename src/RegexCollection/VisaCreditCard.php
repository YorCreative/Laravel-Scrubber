<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class VisaCreditCard implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '((4\d{3}))-?\s?\d{4}-?\s?\d{4}-?\s?\d{4}|3[4,7][\d\s-]{15}$';
    }

    public function getTestableString(): string
    {
        return '4242424242424242';
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
