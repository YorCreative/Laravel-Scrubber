<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies\RegexLoader;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Repositories\RegexCollection;
use YorCreative\Scrubber\Strategies\RegexLoader\Loaders\WildcardRegex;
use YorCreative\Scrubber\Tests\TestCase;

/**
 * Characterization tests for the wildcard namespace loader.
 *
 * These pin the discovery behaviour that WildcardRegex relies on so the underlying
 * class-finding implementation can be changed without silently dropping patterns.
 */
#[Group('RegexLoader')]
#[Group('Unit')]
class WildcardRegexTest extends TestCase
{
    protected function load(): Collection
    {
        $collection = new Collection;
        (new WildcardRegex)->load($collection);

        return $collection;
    }

    public function test_it_only_loads_when_the_wildcard_is_configured()
    {
        Config::set('scrubber.regex_loader', ['*']);
        $this->assertTrue((new WildcardRegex)->canLoad());

        Config::set('scrubber.regex_loader', [RegexCollection::$GOOGLE_API]);
        $this->assertFalse((new WildcardRegex)->canLoad());
    }

    public function test_it_discovers_every_core_regex_class()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', []);
        Config::set('scrubber.exclude_regex', []);

        $loaded = $this->load();

        $this->assertCount(35, $loaded);

        // Spot-check across the collection, including the newest additions.
        foreach (['google_api', 'slack_token', 'iban', 'open_ai_api_key', 'stripe_secret_key'] as $key) {
            $this->assertTrue($loaded->has($key), "Expected [{$key}] to be discovered.");
        }
    }

    public function test_it_discovers_classes_in_custom_namespaces()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        Config::set('scrubber.exclude_regex', []);

        $loaded = $this->load();

        $this->assertCount(36, $loaded);
        $this->assertTrue($loaded->has('custom_regex'));
    }

    public function test_it_excludes_classes_by_unqualified_name()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', []);
        Config::set('scrubber.exclude_regex', ['GoogleApi']);

        $loaded = $this->load();

        $this->assertCount(34, $loaded);
        $this->assertFalse($loaded->has('google_api'));
    }

    public function test_it_excludes_classes_by_fully_qualified_name()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', []);
        Config::set('scrubber.exclude_regex', ['YorCreative\Scrubber\RegexCollection\GoogleApi']);

        $loaded = $this->load();

        $this->assertCount(34, $loaded);
        $this->assertFalse($loaded->has('google_api'));
    }

    public function test_it_excludes_classes_in_custom_namespaces_by_unqualified_name()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        Config::set('scrubber.exclude_regex', ['CustomRegex']);

        $loaded = $this->load();

        $this->assertCount(35, $loaded);
        $this->assertFalse($loaded->has('custom_regex'));
    }

    public function test_it_ignores_unresolvable_exclusions()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', []);
        Config::set('scrubber.exclude_regex', ['NonExistentClass']);

        $this->assertCount(35, $this->load());
    }

    public function test_it_ignores_namespaces_that_resolve_to_nothing()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', ['Totally\\Bogus\\Namespace1']);
        Config::set('scrubber.exclude_regex', []);

        $this->assertCount(35, $this->load());
    }
}
