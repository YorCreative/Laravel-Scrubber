<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class SubscriptionKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '/(?<="GDP-Subscription-Key":")?(?:GDP-Subscription-Key=)?[A-Za-z0-9_~]{4}\K[A-Za-z0-9]{28}/';
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
