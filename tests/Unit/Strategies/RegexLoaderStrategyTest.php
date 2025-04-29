<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies;

use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\RegexCollection\GoogleApi;
use YorCreative\Scrubber\Repositories\RegexCollection;
use YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;
use YorCreative\Scrubber\Tests\Unit\Fixtures\CustomRegex;

class RegexLoaderStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scrubber.config_loader', []);
        Config::set('scrubber.regex_loader', []);
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_default_core()
    {
        Config::set('scrubber.regex_loader', ['*']);
        $this->assertCount(26, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_specific_core()
    {
        Config::set('scrubber.regex_loader', [RegexCollection::$GOOGLE_API]);

        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_specific_core_by_namespace()
    {
        Config::set('scrubber.regex_loader', [GoogleApi::class]);

        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_specific_extended_regex()
    {
        Config::set('scrubber.regex_loader', ['CustomRegex']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_specific_regex_from_core_and_extended()
    {
        Config::set('scrubber.regex_loader', [RegexCollection::$GOOGLE_API, CustomRegex::class]);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(2, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_specific_extended_regex_by_namespace()
    {
        Config::set('scrubber.regex_loader', [CustomRegex::class]);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_wildcard_extended_regex()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(27, app(RegexLoaderStrategy::class)->load());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_wildcard_extended_regex_with_excluded_regex()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.exclude_regex', [RegexCollection::$HEROKU_API_KEY]);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(26, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_config_via_specific_key()
    {
        Config::set('scrubber.config_loader', ['app.my_secret']);
        Config::set('app.my_secret', 'super_secret');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $regex = $regexCollection->get('config::app.my_secret');
        $this->assertInstanceOf(RegexCollectionInterface::class, $regex);
        $this->assertEquals('super_secret', $regex->getPattern());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_can_load_config_via_key_with_wildcard()
    {
        Config::set('scrubber.config_loader', ['app.secrets.*']);
        Config::set('app.secrets.my_secret', 'super_secret');
        Config::set('app.secrets.my_other_secret', 'super_other_secret');
        Config::set('app.secrets.nested.my_secret', 'super_third_secret');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(3, $regexCollection);
        $this->assertEquals('super_secret', $regexCollection->get('config::app.secrets.my_secret')->getPattern());
        $this->assertEquals('super_other_secret', $regexCollection->get('config::app.secrets.my_other_secret')->getPattern());
        $this->assertEquals('super_third_secret', $regexCollection->get('config::app.secrets.nested.my_secret')->getPattern());

        Config::set('scrubber.config_loader', ['*secret']);

        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(3, $regexCollection);
        $this->assertEquals('super_secret', $regexCollection->get('config::app.secrets.my_secret')->getPattern());
        $this->assertEquals('super_other_secret', $regexCollection->get('config::app.secrets.my_other_secret')->getPattern());
        $this->assertEquals('super_third_secret', $regexCollection->get('config::app.secrets.nested.my_secret')->getPattern());
    }

    #[Group('Strategy')]
    #[Group('Unit')]
    public function test_it_escapes_config_values_for_regex()
    {
        Config::set('scrubber.config_loader', ['app.my_secret']);
        Config::set('app.my_secret', 'super~\d.*secret');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $regex = $regexCollection->get('config::app.my_secret');
        $this->assertEquals('super\~\\\\d\.\*secret', $regex->getPattern());
    }

    public function test_it_can_load_wildcard_with_excluded_core_namespace_class()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.exclude_regex', ['GoogleApi']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(26, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_wildcard_with_excluded_fully_qualified_and_unresolvable_classes()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        Config::set('scrubber.exclude_regex', [
            'CustomRegex',
            'EmailAddress',
            'YorCreative\Scrubber\RegexCollection\GoogleApi',
            'NonExistentClass', // Unresolvable class
            RegexCollection::$HEROKU_API_KEY,
        ]);

        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(23, $regexCollection);
    }
}
