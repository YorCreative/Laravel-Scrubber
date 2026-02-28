<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class TroyCreditCard implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '9792\d{12}';
    }

    public function getTestableString(): string
    {
        return '9792020000300001';
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
