<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class GoogleApi implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'AIza[0-9A-Za-z-_]{35}';
    }

    public function getTestableString(): string
    {
        return 'AIzaCgSRDNaBj-GJEs7dUJm-5Hro7hhicAkVTya';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
