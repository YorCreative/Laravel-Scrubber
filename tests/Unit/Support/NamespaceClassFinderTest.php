<?php

namespace YorCreative\Scrubber\Tests\Unit\Support;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\RegexCollection\GoogleApi;
use YorCreative\Scrubber\RegexCollection\SlackToken;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\ScrubberServiceProvider;
use YorCreative\Scrubber\Support\NamespaceClassFinder;
use YorCreative\Scrubber\Tests\TestCase;
use YorCreative\Scrubber\Tests\Unit\Fixtures\AbstractCustomRegex;
use YorCreative\Scrubber\Tests\Unit\Fixtures\CustomRegex;

#[Group('NamespaceClassFinder')]
#[Group('Unit')]
class NamespaceClassFinderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        NamespaceClassFinder::flush();
    }

    public function test_it_resolves_classes_from_a_psr4_namespace()
    {
        $classes = NamespaceClassFinder::getClassesInNamespace('YorCreative\Scrubber\RegexCollection');

        $this->assertContains(GoogleApi::class, $classes);
        $this->assertContains(SlackToken::class, $classes);
        $this->assertCount(35, $classes);
    }

    public function test_it_resolves_classes_from_a_dev_psr4_namespace()
    {
        $classes = NamespaceClassFinder::getClassesInNamespace('YorCreative\Scrubber\Tests\Unit\Fixtures');

        // The finder reports everything declared in the namespace; deciding what is
        // loadable (instantiable, implements the contract) belongs to NamespaceLoader.
        $this->assertSame([AbstractCustomRegex::class, CustomRegex::class], $classes);
    }

    public function test_it_tolerates_a_leading_backslash()
    {
        $this->assertSame(
            NamespaceClassFinder::getClassesInNamespace('YorCreative\Scrubber\RegexCollection'),
            NamespaceClassFinder::getClassesInNamespace('\YorCreative\Scrubber\RegexCollection')
        );
    }

    public function test_it_returns_an_empty_array_for_an_unknown_namespace()
    {
        $this->assertSame([], NamespaceClassFinder::getClassesInNamespace('Totally\Bogus\Namespace1'));
    }

    public function test_it_does_not_descend_into_child_namespaces()
    {
        // This namespace holds two classes directly and a child Loaders namespace,
        // which must not be swept in.
        $classes = NamespaceClassFinder::getClassesInNamespace('YorCreative\Scrubber\Strategies\RegexLoader');

        $this->assertSame([
            'YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface',
            'YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy',
        ], $classes);

        $this->assertNotContains(
            'YorCreative\Scrubber\Strategies\RegexLoader\Loaders\WildcardRegex',
            $classes
        );
    }

    public function test_it_returns_results_consistently_when_cached()
    {
        $first = NamespaceClassFinder::getClassesInNamespace('YorCreative\Scrubber\RegexCollection');
        $second = NamespaceClassFinder::getClassesInNamespace('YorCreative\Scrubber\RegexCollection');

        $this->assertSame($first, $second);
    }

    public function test_it_resolves_classes_at_a_psr4_prefix_root()
    {
        // The namespace is exactly a registered PSR-4 prefix, so there is no
        // relative path segment to append to the mapped directory.
        $classes = NamespaceClassFinder::getClassesInNamespace('YorCreative\\Scrubber');

        $this->assertContains(Scrubber::class, $classes);
        $this->assertContains(ScrubberServiceProvider::class, $classes);
    }

    public function test_it_resolves_classes_registered_in_the_classmap()
    {
        // Apps deployed with an optimised autoloader (composer dump-autoload -o)
        // resolve classes through the classmap rather than the PSR-4 directory scan.
        $loader = new ClassLoader;
        $loader->addClassMap([
            'Scrubber\\ClassMapFixture\\Alpha' => __FILE__,
            'Scrubber\\ClassMapFixture\\Beta' => __FILE__,
            'Scrubber\\ClassMapFixture\\Nested\\Gamma' => __FILE__,
        ]);
        $loader->register();

        try {
            NamespaceClassFinder::flush();

            $this->assertSame([
                'Scrubber\\ClassMapFixture\\Alpha',
                'Scrubber\\ClassMapFixture\\Beta',
            ], NamespaceClassFinder::getClassesInNamespace('Scrubber\\ClassMapFixture'));
        } finally {
            $loader->unregister();
            NamespaceClassFinder::flush();
        }
    }

    /**
     * @return array{0: string, 1: ClassLoader}
     */
    protected function stageAutoloadedClass(string $namespace, string $class, string $strategy): array
    {
        $root = sys_get_temp_dir().'/scrubber-finder-'.uniqid();
        $dir = $root.'/'.str_replace('\\', '/', $namespace);
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/'.$class.'.php', "<?php namespace {$namespace}; class {$class} {}");

        $loader = new ClassLoader;

        // addPsr4('', ...) registers a PSR-4 fallback directory; add('', ...) the PSR-0
        // equivalent. Neither appears in getPrefixesPsr4().
        match ($strategy) {
            'psr4' => $loader->addPsr4('', [$root]),
            'psr0-prefixed' => $loader->add(explode('\\', $namespace)[0].'\\', [$root]),
            default => $loader->add('', [$root]),
        };
        $loader->register();

        return [$root, $loader];
    }

    protected function removeDirectory(string $path): void
    {
        foreach (glob($path.'/*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDirectory($entry) : unlink($entry);
        }
        rmdir($path);
    }

    public function test_it_resolves_classes_from_psr4_fallback_directories()
    {
        [$root, $loader] = $this->stageAutoloadedClass('Acme\\FallbackFour', 'Widget', 'psr4');

        try {
            NamespaceClassFinder::flush();
            $this->assertSame(
                ['Acme\\FallbackFour\\Widget'],
                NamespaceClassFinder::getClassesInNamespace('Acme\\FallbackFour')
            );
        } finally {
            $loader->unregister();
            NamespaceClassFinder::flush();
            $this->removeDirectory($root);
        }
    }

    public function test_it_resolves_classes_from_psr0_directories()
    {
        [$root, $loader] = $this->stageAutoloadedClass('Acme\\FallbackZero', 'Gadget', 'psr0');

        try {
            NamespaceClassFinder::flush();
            $this->assertSame(
                ['Acme\\FallbackZero\\Gadget'],
                NamespaceClassFinder::getClassesInNamespace('Acme\\FallbackZero')
            );
        } finally {
            $loader->unregister();
            NamespaceClassFinder::flush();
            $this->removeDirectory($root);
        }
    }

    public function test_it_resolves_classes_from_a_prefixed_psr0_namespace()
    {
        // A registered PSR-0 prefix rather than a fallback directory.
        [$root, $loader] = $this->stageAutoloadedClass('Acme\\PrefixedZero', 'Sprocket', 'psr0-prefixed');

        try {
            NamespaceClassFinder::flush();
            $this->assertSame(
                ['Acme\\PrefixedZero\\Sprocket'],
                NamespaceClassFinder::getClassesInNamespace('Acme\\PrefixedZero')
            );
        } finally {
            $loader->unregister();
            NamespaceClassFinder::flush();
            $this->removeDirectory($root);
        }
    }

    public function test_it_skips_psr0_prefixes_that_do_not_match_the_namespace()
    {
        [$root, $loader] = $this->stageAutoloadedClass('Acme\\PrefixedZero', 'Sprocket', 'psr0-prefixed');

        try {
            NamespaceClassFinder::flush();
            $this->assertSame([], NamespaceClassFinder::getClassesInNamespace('Unrelated\\Namespace1'));
        } finally {
            $loader->unregister();
            NamespaceClassFinder::flush();
            $this->removeDirectory($root);
        }
    }
}
