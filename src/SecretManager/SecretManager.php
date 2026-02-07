<?php

namespace YorCreative\Scrubber\SecretManager;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Services\SecretService;

class SecretManager
{
    public static function getSecrets(string $provider): Collection
    {
        $providerKey = self::getProviderKey($provider);

        if ($providerKey === null) {
            // Unknown provider - just call getAllSecrets
            return $provider::getAllSecrets();
        }

        $keys = Config::get("scrubber.secret_manager.providers.{$providerKey}.keys", ['*']);

        if (in_array('*', $keys)) {
            return $provider::getAllSecrets();
        }

        $secretCollection = new Collection;

        foreach ($keys as $key) {
            $secretCollection->push($provider::getSpecificSecret($key));
        }

        return $secretCollection;
    }

    /**
     * Get the config key for a provider class.
     */
    protected static function getProviderKey(string $providerClass): ?string
    {
        $providers = SecretService::$providers;

        foreach ($providers as $key => $class) {
            if ($class === $providerClass) {
                return $key;
            }
        }

        return null;
    }
}
