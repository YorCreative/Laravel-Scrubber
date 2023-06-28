<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class ClientSecret implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '/(?<="client_secret":")[A-Za-z0-9_~]{4}\K[A-Za-z0-9_~]{0,}/';
    }

    public function getTestableString(): string
    {
        return 'MaOiS~kjLPSgD_j8hwk4A03AGpS413thq6EQ8gp9';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
