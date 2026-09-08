<?php

namespace YorCreative\Scrubber\Support;

use Composer\Autoload\ClassLoader;

/**
 * Resolves the classes declared directly in a namespace.
 *
 * Discovery is driven by Composer's own autoload maps: the PSR-4 prefix map is used
 * to translate a namespace into the directories backing it, and the classmap is
 * consulted for classes registered ahead of time (an optimised autoloader, or a
 * package using classmap autoloading). Only the namespace itself is inspected —
 * child namespaces are not traversed.
 */
class NamespaceClassFinder
{
    /**
     * @var array<string, array<int, class-string>>
     */
    protected static array $cache = [];

    /**
     * @return array<int, class-string>
     */
    public static function getClassesInNamespace(string $namespace): array
    {
        $namespace = trim($namespace, '\\');

        if (isset(static::$cache[$namespace])) {
            return static::$cache[$namespace];
        }

        $classes = [];

        foreach (static::classLoaders() as $loader) {
            foreach (static::fromPsr4($loader, $namespace) as $class) {
                $classes[$class] = true;
            }

            foreach (static::fromPsr4FallbackDirs($loader, $namespace) as $class) {
                $classes[$class] = true;
            }

            foreach (static::fromPsr0($loader, $namespace) as $class) {
                $classes[$class] = true;
            }

            foreach (static::fromClassMap($loader, $namespace) as $class) {
                $classes[$class] = true;
            }
        }

        $classes = array_keys($classes);
        sort($classes);

        return static::$cache[$namespace] = $classes;
    }

    /**
     * Clear the resolved namespace cache. Intended for tests.
     */
    public static function flush(): void
    {
        static::$cache = [];
    }

    /**
     * @return array<int, ClassLoader>
     */
    protected static function classLoaders(): array
    {
        $loaders = [];

        foreach (spl_autoload_functions() ?: [] as $autoloader) {
            if (is_array($autoloader) && isset($autoloader[0]) && $autoloader[0] instanceof ClassLoader) {
                $loaders[] = $autoloader[0];
            }
        }

        return $loaders;
    }

    /**
     * @return array<int, string>
     */
    protected static function fromPsr4(ClassLoader $loader, string $namespace): array
    {
        $classes = [];

        foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
            $prefix = trim($prefix, '\\');

            if ($prefix !== $namespace && ! str_starts_with($namespace.'\\', $prefix.'\\')) {
                continue;
            }

            $relative = trim(substr($namespace, strlen($prefix)), '\\');
            $suffix = $relative === ''
                ? ''
                : DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $relative);

            $classes = array_merge($classes, static::scan($directories, $suffix, $namespace));
        }

        return $classes;
    }

    /**
     * Composer's PSR-4 fallback directories back an empty prefix ("psr-4": {"": "src/"}),
     * so the namespace maps directly onto a path beneath them.
     *
     * @return array<int, string>
     */
    protected static function fromPsr4FallbackDirs(ClassLoader $loader, string $namespace): array
    {
        $suffix = DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return static::scan($loader->getFallbackDirsPsr4(), $suffix, $namespace);
    }

    /**
     * PSR-0 maps the whole class name onto the path, so both prefixed and fallback
     * directories are appended with the full namespace.
     *
     * @return array<int, string>
     */
    protected static function fromPsr0(ClassLoader $loader, string $namespace): array
    {
        $classes = [];
        $suffix = DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        foreach ($loader->getPrefixes() as $prefix => $directories) {
            $prefix = trim($prefix, '\\');

            if ($prefix !== $namespace && ! str_starts_with($namespace.'\\', $prefix.'\\')) {
                continue;
            }

            $classes = array_merge($classes, static::scan($directories, $suffix, $namespace));
        }

        return array_merge($classes, static::scan($loader->getFallbackDirs(), $suffix, $namespace));
    }

    /**
     * @param  array<int, string>  $directories
     * @return array<int, string>
     */
    protected static function scan(array $directories, string $suffix, string $namespace): array
    {
        $classes = [];

        foreach ($directories as $directory) {
            $path = rtrim($directory, DIRECTORY_SEPARATOR).$suffix;

            if (! is_dir($path)) {
                continue;
            }

            foreach (glob($path.DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
                $classes[] = $namespace.'\\'.basename($file, '.php');
            }
        }

        return $classes;
    }

    /**
     * @return array<int, string>
     */
    protected static function fromClassMap(ClassLoader $loader, string $namespace): array
    {
        $classes = [];

        foreach (array_keys($loader->getClassMap()) as $class) {
            $position = strrpos($class, '\\');

            if ($position === false) {
                continue;
            }

            if (substr($class, 0, $position) === $namespace) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
