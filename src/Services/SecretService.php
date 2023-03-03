<?php

namespace YorCreative\Scrubber\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\SecretManager\Providers\Gitlab;
use YorCreative\Scrubber\SecretManager\SecretManager;

class SecretService
{
    public static array $providers = [
        'gitlab' => Gitlab::class,
    ];

    public static function isEnabled(): bool
    {
        return Config::get('scrubber.secret_manager.enabled') ?? false;
    }

    public static function getEnabledProviders(): Collection
    {
        $enabledProviders = new Collection();

        foreach (Config::get('scrubber.secret_manager.providers') as $key => $configuration) {
            ! $configuration['enabled']
                ?: $enabledProviders->push(self::$providers[$key]);
        }

        return $enabledProviders;
    }

    public static function loadSecrets(Collection $providers): Collection
    {
        $secrets = new Collection();

        $providers->each(function ($provider) use (&$secrets) {
            $secrets = $secrets->merge(SecretManager::getSecrets($provider));
        });

        return $secrets;
    }
}
