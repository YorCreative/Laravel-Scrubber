<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class HerokuApiKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    }

    public function getTestableString(): string
    {
        return '82D1FB27-CB69-BB42-daDf-B6EbFf0f7Bef';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): ?string
    {
        return null;
    }
}
