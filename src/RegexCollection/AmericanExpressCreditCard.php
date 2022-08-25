<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class AmericanExpressCreditCard implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '((4\d{3})(34\d{1})|(37\d{1}))-?\s?\d{4}-?\s?\d{4}-?\s?\d{4}|3[4,7][\d\s-]{15}$';
    }

    public function getTestableString(): string
    {
        return '378282246310005';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
