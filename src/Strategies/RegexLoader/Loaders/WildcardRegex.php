<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class WildcardRegex extends NamespaceLoader
{
    public function canLoad(): bool
    {
        return in_array('*', Config::get('scrubber.regex_loader'));
    }

    public function load(Collection &$regexCollection): void
    {
        foreach ($this->getNamespaces() as $namespace) {

            foreach ($this->getRegexClassesInNamespace($namespace) as $fullyQualifiedClassName) {
                $this->loadRegex($fullyQualifiedClassName, $regexCollection);
            }
        }
    }

    protected function getRegexClassesInNamespace(string $namespace): array
    {
        return Collection::make(ClassFinder::getClassesInNamespace($namespace))
            ->filter(fn ($class) => $this->isRegexClass($class))
            ->toArray();
    }
}
