<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class AwsAccessKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'AKIA[0-9A-Z]{16}';
    }

    public function getTestableString(): string
    {
        return 'AKIAB1VCNS1Q2EDZD6QZ';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
