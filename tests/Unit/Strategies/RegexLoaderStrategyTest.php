<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\RegexCollection\GoogleApi;
use YorCreative\Scrubber\Repositories\RegexCollection;
use YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;
use YorCreative\Scrubber\Tests\Unit\Fixtures\CustomRegex;

#[Group('Strategy')]
#[Group('Unit')]
class RegexLoaderStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scrubber.config_loader', []);
        Config::set('scrubber.regex_loader', []);
    }

    public function test_it_can_load_default_core()
    {
        Config::set('scrubber.regex_loader', ['*']);
        $this->assertCount(31, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_specific_core()
    {
        Config::set('scrubber.regex_loader', [RegexCollection::$GOOGLE_API]);
        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_specific_core_by_namespace()
    {
        Config::set('scrubber.regex_loader', [GoogleApi::class]);
        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_specific_extended_regex()
    {
        Config::set('scrubber.regex_loader', ['CustomRegex']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_specific_regex_from_core_and_extended()
    {
        Config::set('scrubber.regex_loader', [RegexCollection::$GOOGLE_API, CustomRegex::class]);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(2, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_specific_extended_regex_by_namespace()
    {
        Config::set('scrubber.regex_loader', [CustomRegex::class]);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_wildcard_extended_regex()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(32, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_can_load_wildcard_extended_regex_with_excluded_regex()
    {
        Config::set('scrubber.regex_loader', ['*']);
        Config::set('scrubber.exclude_regex', [RegexCollection::$HEROKU_API_KEY]);
        Config::set('scrubber.custom_regex_namespaces', ['YorCreative\\Scrubber\\Tests\\Unit\\Fixtures']);
        $this->assertCount(31, app(RegexLoaderStrategy::class)->load());
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
        $this->assertCount(31, app(RegexLoaderStrategy::class)->load());
    }

    public function test_it_excludes_short_config_values_by_default()
    {
        Config::set('scrubber.config_loader', ['*token']);
        Config::set('livewire.release_token', 'a');
        Config::set('app.api_token', 'a-real-secret-token');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $this->assertNull($regexCollection->get('config::livewire.release_token'));
        $this->assertNotNull($regexCollection->get('config::app.api_token'));
    }

    public function test_it_respects_custom_config_loader_min_length()
    {
        Config::set('scrubber.config_loader', ['*token']);
        Config::set('scrubber.config_loader_min_length', 8);
        Config::set('app.short_token', 'abcd');
        Config::set('app.long_token', 'abcdefgh');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $this->assertNull($regexCollection->get('config::app.short_token'));
        $this->assertNotNull($regexCollection->get('config::app.long_token'));
    }

    public function test_it_can_disable_min_length_filter()
    {
        Config::set('scrubber.config_loader', ['*token']);
        Config::set('scrubber.config_loader_min_length', 0);
        Config::set('app.tiny_token', 'a');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $this->assertNotNull($regexCollection->get('config::app.tiny_token'));
    }

    public function test_it_excludes_config_keys_matching_exclusion_patterns()
    {
        Config::set('scrubber.config_loader', ['*token']);
        Config::set('scrubber.config_loader_exclusions', ['livewire.release_token']);
        Config::set('livewire.release_token', 'some-valid-length-token');
        Config::set('app.api_token', 'another-valid-token');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $this->assertNull($regexCollection->get('config::livewire.release_token'));
        $this->assertNotNull($regexCollection->get('config::app.api_token'));
    }

    public function test_it_supports_wildcard_exclusion_patterns()
    {
        Config::set('scrubber.config_loader', ['livewire.*', 'app.api_token']);
        Config::set('scrubber.config_loader_exclusions', ['livewire.*']);
        Config::set('livewire.release_token', 'some-token-value');
        Config::set('livewire.app_key', 'some-key-value');
        Config::set('app.api_token', 'real-secret');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $this->assertNull($regexCollection->get('config::livewire.release_token'));
        $this->assertNull($regexCollection->get('config::livewire.app_key'));
        $this->assertNotNull($regexCollection->get('config::app.api_token'));
    }

    public function test_it_excludes_non_string_config_values()
    {
        Config::set('scrubber.config_loader', ['app.bool_token', 'app.int_token', 'app.real_token']);
        Config::set('scrubber.config_loader_min_length', 0);
        Config::set('app.bool_token', true);
        Config::set('app.int_token', 12345);
        Config::set('app.real_token', 'valid-secret');
        $regexCollection = app(RegexLoaderStrategy::class)->load();
        $this->assertCount(1, $regexCollection);
        $this->assertNull($regexCollection->get('config::app.bool_token'));
        $this->assertNull($regexCollection->get('config::app.int_token'));
        $this->assertNotNull($regexCollection->get('config::app.real_token'));
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
        $this->assertCount(28, $regexCollection);
    }
}
