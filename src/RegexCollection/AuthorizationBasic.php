<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class AuthorizationBasic implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '(?<=basic) [a-zA-Z0-9=:_\+\/-]{5,100}';
    }

    public function getTestableString(): string
    {
        return 'basic f9Iu+YwMiJEsQu/vBHlbUNZRkN/ihdB1sNTU';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
