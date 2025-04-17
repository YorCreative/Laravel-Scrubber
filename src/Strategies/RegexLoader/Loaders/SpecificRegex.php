<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class SpecificRegex extends NamespaceLoader
{
    public function canLoad(): bool
    {
        return ! in_array('*', Config::get('scrubber.regex_loader'));
    }

    public function load(Collection &$regexCollection): void
    {
        $regexesToLoad = $this->getRegexesToLoad();
        foreach ($regexesToLoad as $regexClass) {

            if ($this->isRegexClass($regexClass)) {
                $this->loadRegex($regexClass, $regexCollection);

                continue;
            }

            foreach ($this->getNamespaces() as $namespace) {
                $fullyQualifiedClassName = $namespace.'\\'.$regexClass;
                if ($this->isRegexClass($fullyQualifiedClassName)) {
                    $this->loadRegex($fullyQualifiedClassName, $regexCollection);

                    continue;
                }
            }
        }
    }
}
