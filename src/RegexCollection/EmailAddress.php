<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class EmailAddress implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}';
    }

    public function getTestableString(): string
    {
        return 'example@test.com';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
