<?php

namespace YorCreative\Scrubber\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use YorCreative\Scrubber\SecretManager\Providers\AwsSecretsManager;
use YorCreative\Scrubber\SecretManager\Providers\Gitlab;
use YorCreative\Scrubber\SecretManager\Providers\Vault;
use YorCreative\Scrubber\SecretManager\SecretManager;

class SecretService
{
    public static array $providers = [
        'gitlab' => Gitlab::class,
        'aws' => AwsSecretsManager::class,
        'vault' => Vault::class,
    ];

    public static function isEnabled(): bool
    {
        return Config::get('scrubber.secret_manager.enabled') ?? false;
    }

    public static function getEnabledProviders(): Collection
    {
        $enabledProviders = new Collection;
        $configuredProviders = Config::get('scrubber.secret_manager.providers', []);

        foreach ($configuredProviders as $key => $configuration) {
            if (! isset($configuration['enabled']) || ! $configuration['enabled']) {
                continue;
            }

            if (! isset(self::$providers[$key])) {
                Log::warning("Scrubber: Unknown secret provider '{$key}' configured but not registered");

                continue;
            }

            $enabledProviders->push(self::$providers[$key]);
        }

        return $enabledProviders;
    }

    public static function loadSecrets(Collection $providers): Collection
    {
        $secrets = new Collection;

        $providers->each(function ($provider) use (&$secrets) {
            try {
                $secrets = $secrets->merge(SecretManager::getSecrets($provider));
            } catch (\Throwable $e) {
                Log::warning("Scrubber: Failed to load secrets from provider {$provider}: ".$e->getMessage());
            }
        });

        return $secrets;
    }
}
