<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class Password implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '/password/';
    }

    public function getTestableString(): string
    {
        return 'Test1234!';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
