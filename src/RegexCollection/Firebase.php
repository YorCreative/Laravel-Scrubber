<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class Firebase implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'AAAA[A-Za-z0-9_-]{7}:[A-Za-z0-9_-]{140}';
    }

    public function getTestableString(): string
    {
        return 'AAAAF6Xc3h_:qZ-O6KwdYHmyBII1b7Z0PJ2fhqFnLyOFSXvKUX6c1YnkoubNl8r4cX0WomlWL_3qcGu6zac_B0FjS_GicJepjBs4_zwJ4-R5m3Z7dQKbrjEV-Mqt14jMSpd7pwxJbHyL5ayaLoQmxC9O';
    }
}
