<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class MasterCardCreditCard implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '((5[1-5]\d{2}))-?\s?\d{4}-?\s?\d{4}-?\s?\d{4}|3[4,7][\d\s-]{15}$';
    }

    public function getTestableString(): string
    {
        return '5555555555554444';
    }
}
