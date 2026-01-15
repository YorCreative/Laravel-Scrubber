<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class IpAddressV4 implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        // Matches IPv4 addresses: 192.168.1.1, 10.0.0.1, etc.
        return '\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b';
    }

    public function getTestableString(): string
    {
        return '192.168.0.1';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): string
    {
        return '***.***.***.***';
    }
}
