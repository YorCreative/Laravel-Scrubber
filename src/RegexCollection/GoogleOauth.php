<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class GoogleOauth implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'ya29\.[0-9A-Za-z\-_]+';
    }

    public function getTestableString(): string
    {
        return 'ya29.EZfHX1INB-Z1gYMig_e7jG5n1p3a8aodX';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
