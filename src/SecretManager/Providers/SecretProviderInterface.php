<?php

namespace YorCreative\Scrubber\SecretManager\Providers;

use Illuminate\Support\Collection;
use YorCreative\Scrubber\SecretManager\Secret;

interface SecretProviderInterface
{
    public static function getAllSecrets(): Collection;

    public static function getSpecificSecret(string $key): Secret;
}
