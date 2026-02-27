<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class AuthorizationBearer implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '(?<=bearer\s)[a-zA-Z0-9_\-\.=:_\+\/]*';
    }

    public function getTestableString(): string
    {
        return 'bearer YUzqdOgX_PXwGw7jkY1SVOEwEDDOkfKD3sAPGMKFN2smuVg9w_B9-/wQQl=UG_5_Pz56kLfMCnPXoY+10tS9JO5Sw8B0ho65';
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
