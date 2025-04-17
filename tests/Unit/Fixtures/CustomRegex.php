<?php

namespace YorCreative\Scrubber\Tests\Unit\Fixtures;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class CustomRegex implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '/custom_regex/';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getTestableString(): string
    {
        return 'custom_regex';
    }
}
