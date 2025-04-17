<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

abstract class NamespaceLoader implements LoaderInterface
{
    protected function loadRegex(string $fullyQualifiedClassName, Collection &$regexCollection): void
    {
        $regex = (new $fullyQualifiedClassName);
        $regexCollection = $regexCollection->merge([
            Str::snake(class_basename($fullyQualifiedClassName)) => $regex,
        ]);
    }

    protected function isRegexClass(string $fullyQualifiedClassName): bool
    {
        return class_exists($fullyQualifiedClassName) && is_a($fullyQualifiedClassName, RegexCollectionInterface::class, true);
    }

    protected function getRegexesToLoad(): array
    {
        return Config::get('scrubber.regex_loader', []);
    }

    protected function getNamespaces(): array
    {
        return array_merge(
            Config::get('scrubber.custom_regex_namespaces', []),
            ['YorCreative\Scrubber\RegexCollection']
        );
    }
}
