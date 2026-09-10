<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class GithubPersonalAccessToken implements RegexCollectionInterface
{
    /**
     * Modern GitHub token shapes: classic PATs (ghp_), OAuth (gho_), user-to-server
     * (ghu_), server-to-server (ghs_) and refresh (ghr_) tokens, plus fine-grained
     * PATs (github_pat_). The older user:token@github.com form is covered separately
     * by GithubAccessToken.
     */
    public function getPattern(): string
    {
        return '(?<![A-Za-z0-9_])(?:gh[pousr]_[A-Za-z0-9]{36,}|github_pat_[A-Za-z0-9_]{22,})';
    }

    public function getTestableString(): string
    {
        return 'ghp_EXAMPLENOTAREALTOKEN0000000000000000';
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
