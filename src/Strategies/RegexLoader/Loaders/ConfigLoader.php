<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class ConfigLoader implements LoaderInterface
{
    public function canLoad(): bool
    {
        return ! empty(Config::get('scrubber.config_loader', []));
    }

    public function load(Collection &$regexCollection): void
    {
        foreach ($this->getConfigs() as $configKey => $configValue) {
            $regexCollection = $regexCollection->merge([
                'config::'.$configKey => self::generateRegexClassForConfig($configValue),
            ]);
        }
    }

    public function getConfigs(): array
    {
        $configCollection = collect();
        $allConfig = collect(Config::all())->dot();
        $keyPaterns = Config::get('scrubber.config_loader', []);
        foreach ($keyPaterns as $keyPattern) {
            if (str_contains($keyPattern, '*')) {
                $configCollection = $configCollection->merge($allConfig->filter(function ($value, $key) use ($keyPattern) {
                    return Str::is($keyPattern, $key);
                })->filter());

                continue;
            }
            $configCollection = $configCollection->merge(collect([$keyPattern => Config::get($keyPattern)])->dot()->filter());
        }

        return $configCollection->unique()->toArray();
    }

    protected static function generateRegexClassForConfig(string $config): RegexCollectionInterface
    {
        $class = new class implements RegexCollectionInterface
        {
            public string $pattern;

            public function isSecret(): bool
            {
                return false;
            }

            public function getPattern(): string
            {
                return $this->pattern;
            }

            public function getTestableString(): string
            {
                return $this->pattern;
            }

            public function getReplacementValue(): ?string
            {
                return null;
            }

            public function setPattern(string $encryptedSecret)
            {
                $this->pattern = $encryptedSecret;
            }
        };

        $class->setPattern(preg_quote($config, '~'));

        return $class;
    }
}
