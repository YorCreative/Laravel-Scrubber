<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class SshDcPrivateKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '(?<=-----BEGIN EC PRIVATE KEY-----)(?s)(.*END EC PRIVATE KEY)';
    }

    public function getTestableString(): string
    {
        return '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIKEubpBiHkZQYlORbCy8gGTz8tzrWsjBJA6GfFCrQ98coAoGCCqGSM49
AwEHoUQDQgAEOr6rMmRRNKuZuwws/hWwFTM6ECEEaJGGARCJUO4UfoURl8b4JThG
t8VDFKeR2i+ZxE+xh/wTBaJ/zvtSqZiNnQ==
-----END EC PRIVATE KEY-----';
    }
}
