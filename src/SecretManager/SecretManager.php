<?php

namespace YorCreative\Scrubber\SecretManager;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class SecretManager
{
    /**
     * @param $provider
     * @return Collection
     */
    public static function getSecrets($provider): Collection
    {
        // check if configuration wants all secrets or specific secrets
        $keys = Config::get('scrubber.secret_manager.providers.gitlab.keys');

        if (in_array('*', $keys)) {
            return $provider::getAllSecrets();
        }

        $secretCollection = new Collection();

        foreach ($keys as $key) {
            $secretCollection->push($provider::getSpecificSecret($key));
        }

        return $secretCollection;
    }
}
