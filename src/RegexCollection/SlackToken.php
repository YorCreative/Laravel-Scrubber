<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class SlackToken implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'xox[a-zA-Z]-[a-zA-Z0-9-]+';
    }

    public function getTestableString(): string
    {
        return 'xoxs-EO1ri9VFw3a0Ok4Bf769hR92zyVSef0JEZ5PDoEbCs7';
    }
}
