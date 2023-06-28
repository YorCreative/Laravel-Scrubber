<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class SubscriptionKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '^.{32}$';
    }

    public function getTestableString(): string
    {
        return '6614e0aas33410osadkml30adSDopasd';
    }

    public function isSecret(): bool
    {
        return false;
    }

}
