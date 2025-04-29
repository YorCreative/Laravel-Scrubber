<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use YorCreative\Scrubber\Repositories\RegexCollection;

class WildcardRegex extends NamespaceLoader
{
    protected const CONFIG_REGEX_LOADER = 'scrubber.regex_loader';

    protected const CONFIG_EXCLUDE_REGEX = 'scrubber.exclude_regex';

    public function canLoad(): bool
    {
        return in_array('*', Config::get(self::CONFIG_REGEX_LOADER, []), true);
    }

    public function load(Collection &$regexCollection): void
    {
        // Get excluded classes and resolve RegexCollection static properties
        $excludedClasses = $this->resolveExcludedClasses(Config::get(self::CONFIG_EXCLUDE_REGEX, []));

        foreach ($this->getNamespaces() as $namespace) {
            $classes = $this->getRegexClassesInNamespace($namespace, $excludedClasses);
            foreach ($classes as $class) {
                $this->loadRegex($class, $regexCollection);
            }
        }
    }

    protected function getRegexClassesInNamespace(string $namespace, array $excludedClasses): array
    {
        return array_filter(
            ClassFinder::getClassesInNamespace($namespace),
            fn (string $class): bool => $this->isRegexClass($class) && ! in_array($class, $excludedClasses, true)
        );
    }

    protected function resolveExcludedClasses(array $excludedClasses): array
    {
        $reflection = new ReflectionClass('YorCreative\Scrubber\Repositories\RegexCollection');
        $regexCollectionArray = $reflection->getStaticProperties();

        return array_map(function ($class) use ($regexCollectionArray) {
            $regexPatterns = array_filter($regexCollectionArray, function ($regex) use ($class) {
                return $regex === $class;
            });

            if (!empty($regexPatterns)) {
                return "YorCreative\\Scrubber\\RegexCollection\\$class";
            }

            // If the class is unqualified, attempt to resolve within custom namespaces
            if (strpos($class, '\\') === false) {
                foreach ($this->getNamespaces() as $namespace) {
                    $potentialClass = $namespace.'\\'.$class;
                    if (class_exists($potentialClass)) {
                        return $potentialClass;
                    }
                }
            }

            return $class;
        }, $excludedClasses);
    }
}
