<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies\TapLoader;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Strategies\TapLoader\Loaders\SpecificChannel;
use YorCreative\Scrubber\Strategies\TapLoader\TapLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('TapLoader')]
#[Group('Unit')]
class SpecificChannelTest extends TestCase
{
    public function test_can_load_returns_false_for_empty_array()
    {
        Config::set('scrubber.tap_channels', []);

        $loader = new SpecificChannel;

        $this->assertFalse($loader->canLoad());
    }

    public function test_can_load_returns_true_for_single_channel()
    {
        Config::set('scrubber.tap_channels', ['single']);

        $loader = new SpecificChannel;

        $this->assertTrue($loader->canLoad());
    }

    public function test_can_load_returns_false_for_multiple_channels()
    {
        Config::set('scrubber.tap_channels', ['single', 'papertrail']);

        $loader = new SpecificChannel;

        $this->assertFalse($loader->canLoad());
    }

    public function test_can_load_returns_false_for_wildcard()
    {
        Config::set('scrubber.tap_channels', ['*']);

        $loader = new SpecificChannel;

        $this->assertFalse($loader->canLoad());
    }

    public function test_empty_array_does_not_cause_error_through_strategy()
    {
        $config = app()->make('config');
        $config->set('scrubber.tap_channels', []);

        app(TapLoaderStrategy::class)->load($config);

        foreach ($config->get('logging.channels') as $channel) {
            $this->assertArrayNotHasKey('tap', $channel);
        }
    }
}
