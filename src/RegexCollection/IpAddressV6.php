<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class IpAddressV6 implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        // Matches IPv6 addresses including compressed forms
        return '\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b|\b(?:[0-9a-fA-F]{1,4}:){1,7}:\b|\b(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}\b|\b(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}\b|\b(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}\b|\b::(?:[0-9a-fA-F]{1,4}:){0,5}[0-9a-fA-F]{1,4}\b|\b[0-9a-fA-F]{1,4}::(?:[0-9a-fA-F]{1,4}:){0,5}[0-9a-fA-F]{1,4}\b';
    }

    public function getTestableString(): string
    {
        return '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): string
    {
        return '****:****:****:****:****:****:****:****';
    }
}
