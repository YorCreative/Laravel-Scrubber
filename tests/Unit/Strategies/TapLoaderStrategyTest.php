<?php

namespace YorCreative\Scrubber\Test\Unit\Strategies;

use YorCreative\Scrubber\Strategies\TapLoader\TapLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;

class TapLoaderStrategyTest extends TestCase
{
    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_wildcard_channels_and_tap()
    {
        $config = app()->make('config');

        $config->set('scrubber.tap_channels', ['*']);

        app(TapLoaderStrategy::class)->load($config);

        foreach ($config->get('logging.channels') as $channel) {
            $this->assertArrayHasKey('tap', $channel);
        }
    }

    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_specific_channel_and_tap()
    {
        $config = app()->make('config');

        $config->set('scrubber.tap_channels', ['single']);

        app(TapLoaderStrategy::class)->load($config);

        $this->assertArrayHasKey('tap', $config->get('logging.channels.single'));
        $this->assertArrayNotHasKey('tap', $config->get('logging.channels.papertrail'));
    }

    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_multiple_channels_and_tap()
    {
        $config = app()->make('config');

        $config->set('scrubber.tap_channels', ['single', 'papertrail']);

        app(TapLoaderStrategy::class)->load($config);

        $this->assertArrayHasKey('tap', $config->get('logging.channels.single'));
        $this->assertArrayHasKey('tap', $config->get('logging.channels.papertrail'));
    }

    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_disable_tap()
    {
        $config = app()->make('config');

        $config->set('scrubber.tap_channels', false);

        app(TapLoaderStrategy::class)->load($config);

        foreach ($config->get('logging.channels') as $channel) {
            $this->assertArrayNotHasKey('tap', $channel);
        }
    }

    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_wont_tap_with_invalid_input_when_not_disabled()
    {
        $config = app()->make('config');

        $config->set('scrubber.tap_channels', 1);

        app(TapLoaderStrategy::class)->load($config);

        foreach ($config->get('logging.channels') as $channel) {
            $this->assertArrayNotHasKey('tap', $channel);
        }
    }
}
