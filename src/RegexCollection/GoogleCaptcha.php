<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class GoogleCaptcha implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '6L[0-9A-Za-z-_]{38}|^6[0-9a-zA-Z_-]{39}$';
    }

    public function getTestableString(): string
    {
        return '6L-7sFQU_mgT_Rall6yDGpO5jzzg1oc9usCvScU6';
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
