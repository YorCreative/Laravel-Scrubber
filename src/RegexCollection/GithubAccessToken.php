<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class GithubAccessToken implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '[a-zA-Z0-9_-]*:[a-zA-Z0-9_\-]+@github\.com';
    }

    public function getTestableString(): string
    {
        return 'IfYjoQdbgSjndNfSYPRgaoHW9xfciBFVKKEW0F0Ng3PlqnwbB7T5CPv0ZoGqvZiujNqAIFgDAPRv22mQT_1CtHzrGQw:3YrJjzavd8GzK5LV1vqB-0hIXFP6fbCz4CK5WXrD3upteA6v3UEqryvAOO_Mn3_cY6drhYAHQxRZM0WT6A476dTIJ7tb_uWkyFr@github.com';
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
