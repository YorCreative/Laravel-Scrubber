<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class TwilioApiKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'SK[0-9a-fA-F]{32}';
    }

    public function getTestableString(): string
    {
        return 'SKd7fFD6A5CBdde85eCf76B69EE9de6cCa';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
