<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\SecretManager\Secret;
use YorCreative\Scrubber\Services\SecretService;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class SecretLoader implements LoaderInterface
{
    public function canLoad(): bool
    {
        return SecretService::isEnabled();
    }

    public function load(Collection &$regexCollection): void
    {
        try {
            $providers = SecretService::getEnabledProviders();
            $secrets = SecretService::loadSecrets($providers);
            $secrets->each(function ($secret) use (&$regexCollection) {
                $regexCollection = $regexCollection->merge([
                    $secret->getKey() => self::generateRegexClassForSecret($secret), ]
                );
            });
        } catch (\Throwable $e) {
            Log::warning('Scrubber: Failed to load secrets for scrubbing: '.$e->getMessage());
        }
    }

    protected static function generateRegexClassForSecret(Secret $secret): RegexCollectionInterface
    {
        $class = new class implements RegexCollectionInterface
        {
            public string $pattern;

            public function isSecret(): bool
            {
                return true;
            }

            public function getPattern(): string
            {
                return $this->pattern;
            }

            public function getTestableString(): string
            {
                return $this->pattern;
            }

            public function setPattern(string $encryptedSecret)
            {
                $this->pattern = $encryptedSecret;
            }
        };

        $class->setPattern(preg_quote($secret->getVariable(), '~'));

        return $class;
    }
}
