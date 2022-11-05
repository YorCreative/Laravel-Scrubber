<?php

namespace YorCreative\Scrubber\Test\Unit\Strategies;

use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Strategies\TapLoader\TapLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;

class TapLoaderStrategyTest extends TestCase
{
    /**
     * @test
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_wildcard_channels_and_tap()
    {
        $config = app()->make('config');

        Config::set('scrubber.tap_channels', ['*']);

        app(TapLoaderStrategy::class)->load($config);

        foreach($config->get('logging.channels') as $channel) {
            $this->assertArrayHasKey('tap', $channel);
        }
    }

    /**
     * @test
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_specific_channel_and_tap()
    {
        $config = app()->make('config');

        Config::set('scrubber.tap_channels', ['single']);

        app(TapLoaderStrategy::class)->load($config);

        $this->assertArrayHasKey('tap', $config->get('logging.channels.single'));
    }

    /**
     * @test
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_multiple_channels_and_tap()
    {
        $config = app()->make('config');

        Config::set('scrubber.tap_channels', ['single', 'papertrail']);

        app(TapLoaderStrategy::class)->load($config);

        $this->assertArrayHasKey('tap', $config->get('logging.channels.single'));
        $this->assertArrayHasKey('tap', $config->get('logging.channels.papertrail'));
    }

    /**
     * @test
     * @group Strategy
     * @group Unit
     */
    public function it_can_default_to_load_wildcard_channels_and_tap()
    {
        $config = app()->make('config');

        Config::set('scrubber.tap_channels', null);


        app(TapLoaderStrategy::class)->load($config);

        foreach($config->get('logging.channels') as $channel) {
            $this->assertArrayHasKey('tap', $channel);
        }
    }
}
