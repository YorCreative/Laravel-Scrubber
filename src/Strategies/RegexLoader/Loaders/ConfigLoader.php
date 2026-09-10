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

        $minLength = Config::get('scrubber.config_loader_min_length', 4);
        $exclusions = Config::get('scrubber.config_loader_exclusions', []);

        return $configCollection
            ->filter(function ($value, $key) use ($minLength, $exclusions) {
                if (! is_string($value)) {
                    return false;
                }

                if (strlen($value) < $minLength) {
                    return false;
                }

                foreach ($exclusions as $exclusionPattern) {
                    if (Str::is($exclusionPattern, $key)) {
                        return false;
                    }
                }

                return true;
            })
            ->unique()
            ->toArray();
    }

    protected static function generateRegexClassForConfig(string $config): RegexCollectionInterface
    {
        $class = new class implements RegexCollectionInterface
        {
            public string $pattern;

            public string $testable = '';

            public function isSecret(): bool
            {
                return false;
            }

            public function getPattern(): string
            {
                return $this->pattern;
            }

            /**
             * The raw config value, not the quoted pattern. scrubber:validate matches
             * the pattern against this, so returning the quoted form would report every
             * value containing a regex metacharacter as failing.
             */
            public function getTestableString(): string
            {
                return $this->testable;
            }

            public function setTestableString(string $testable): void
            {
                $this->testable = $testable;
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

        // No delimiter is passed to preg_quote(): RegexRepository escapes the tilde
        // delimiter itself, so quoting it here too would double-escape and leave the
        // pattern uncompilable, silently skipping the value.
        $class->setPattern(preg_quote($config));
        $class->setTestableString($config);

        return $class;
    }
}
