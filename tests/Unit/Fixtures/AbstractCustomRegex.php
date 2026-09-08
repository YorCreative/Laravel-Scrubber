<?php

namespace YorCreative\Scrubber\Tests\Unit\Fixtures;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

/**
 * Present purely to prove the wildcard loader skips non-instantiable classes.
 * If it is ever constructed the suite fatals, which is the point.
 */
abstract class AbstractCustomRegex implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'abstract-should-never-load';
    }

    public function getTestableString(): string
    {
        return 'abstract-should-never-load';
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
