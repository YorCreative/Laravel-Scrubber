<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class EmailAddress implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,63}';
    }

    public function getTestableString(): string
    {
        return 'johndoe321@example.associates';
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
