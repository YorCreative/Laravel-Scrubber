<?php

namespace YorCreative\Scrubber\Tests\Fixtures;

/**
 * Plain object with one public and two non-public properties.
 */
class MixedVisibility
{
    public string $pub = 'public-value';

    protected string $prot = 'protected-value';

    private string $priv = 'sk_live_PRIVATEVALUE0001';
}
