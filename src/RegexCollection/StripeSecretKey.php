<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class StripeSecretKey implements RegexCollectionInterface
{
    /**
     * Covers all three Stripe credential shapes: secret keys (sk_live_/sk_test_),
     * restricted keys (rk_live_/rk_test_) and webhook signing secrets (whsec_).
     * The underscore after the prefix is what separates these from OpenAI's sk-.
     */
    public function getPattern(): string
    {
        return '(?<![A-Za-z0-9_])(?:(?:sk|rk)_(?:live|test)_[A-Za-z0-9]{16,}|whsec_[A-Za-z0-9]{16,})';
    }

    public function getTestableString(): string
    {
        return 'sk_live_EXAMPLENOTAREAL01';
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
