<?php

namespace YorCreative\Scrubber\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\SecretManager\Secret;
use YorCreative\Scrubber\Services\SecretService;

class RegexRepository
{
    public Collection $regexCollection;

    public function __construct()
    {
        $this->regexCollection = new Collection();
        $this->loadRegexClasses();
    }

    /**
     * @return Collection
     */
    public function getRegexCollection(): Collection
    {
        return $this->regexCollection;
    }

    protected function loadRegexClasses(): void
    {
        /**
         * Default Regex Collection
         */
        foreach (File::files(dirname(__DIR__, 1).'/RegexCollection') as $regexClass) {
            $regex = (new ('YorCreative\Scrubber\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

            $this->regexCollection = $this->regexCollection->merge([
                Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
            ]);
        }

        /**
         * Extended Regex Collection
         */
        $path = base_path('App/Scrubber/RegexCollection');

        if (File::exists($path)) {
            foreach (File::files($path) as $regexClass) {
                $regex = (new ('App\\Scrubber\\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

                $this->regexCollection = $this->regexCollection->merge([
                    Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
                ]);
            }
        }

        /**
         * Load in Enabled Secret Manager Providers
         */
        if (SecretService::isEnabled()) {
            $providers = SecretService::getEnabledProviders();
            $secrets = SecretService::loadSecrets($providers);
            $secrets->each(function ($secret) use (&$collection) {
                $this->regexCollection = $this->regexCollection->merge([
                    $secret->getKey() => self::generateRegexClassForSecret($secret), ]
                );
            });
        }
    }

    /**
     * @param  string  $regex
     * @param  string  $content
     * @param  int  $hits
     * @return string
     */
    public static function checkAndSanitize(string $regex, string $content, int &$hits = 0): string
    {
        return preg_replace("~$regex~i", config('scrubber.redaction'), $content, -1, $hits);
    }

    protected static function generateRegexClassForSecret(Secret $secret)
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

        $class->setPattern($secret->getVariable());

        return $class;
    }
}
