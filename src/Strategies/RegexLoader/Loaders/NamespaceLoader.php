<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

abstract class NamespaceLoader implements LoaderInterface
{

    protected function loadRegex(string $fqcn, Collection &$regexCollection): void
    {
        $regex = (new $fqcn());
        $regexCollection = $regexCollection->merge([
            Str::snake(class_basename($fqcn)) => $regex,
        ]);
    }

    protected function isRegexClass(string $fqcn): bool
    {    
        return class_exists($fqcn) && is_a($fqcn, RegexCollectionInterface::class, true);
    }

    protected function getRegexesToLoad(): array
    {
        return Config::get('scrubber.regex_loader',[]);
    }

    protected function getNamespaces(): array
    {
        return array_merge(
            Config::get('scrubber.custom_regex_namespaces',[]),
            ['YorCreative\Scrubber\RegexCollection']
        );
    }

}