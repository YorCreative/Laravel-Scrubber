<?php

namespace YorCreative\Scrubber\Tests\Fixtures;

/**
 * Object exposing a __toString() but no JSON contract.
 */
class StringableThing
{
    public string $shown = 'shown-value';

    private string $hidden = 'hidden-value';

    public function __toString(): string
    {
        return 'stringified';
    }
}
