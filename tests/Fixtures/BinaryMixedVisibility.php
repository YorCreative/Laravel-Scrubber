<?php

namespace YorCreative\Scrubber\Tests\Fixtures;

/**
 * No JSON contract, mixed visibility, and a binary value that makes
 * json_encode() fail -- exercising the fallback for a plain object.
 */
class BinaryMixedVisibility
{
    public string $binary = "\xB1";

    protected string $prot = 'protected-value';

    private string $priv = 'sk_live_PRIVATEVALUE0001';
}
