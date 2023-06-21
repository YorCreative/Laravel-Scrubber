<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class Password implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '/(?:"password": "([^"]*)")|\b[\w!@#$%^&*()\-+=]{8,}?\b/m';
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
