<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class MailgunApiKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'key-[0-9a-zA-Z]{32}';
    }

    public function getTestableString(): string
    {
        return 'key-XIvJaxdKlIynScWweyN5f2bqhwkixHQR';
    }
}
