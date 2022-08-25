<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class TwilioAccountSid implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'AC[a-zA-Z0-9_\-]{32}';
    }

    public function getTestableString(): string
    {
        return 'ACTdJJSNGdZ5aqJL4V6KGlfYQe7w-9TV5O';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
