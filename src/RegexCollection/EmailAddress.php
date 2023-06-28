<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class EmailAddress implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,63}/';
    }

    public function getTestableString(): string
    {
        return 'johndoe321@example.associates';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
