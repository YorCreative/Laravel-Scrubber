<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class DiscoverCreditCard implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '((6011))-?\s?\d{4}-?\s?\d{4}-?\s?\d{4}|3[4,7][\d\s-]{15}$';
    }

    public function getTestableString(): string
    {
        return '6011111111111117';
    }
}
