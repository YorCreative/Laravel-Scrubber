<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies;

use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Repositories\RegexCollection;
use YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;

class RegexLoaderStrategyTest extends TestCase
{
    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_default_core()
    {
        $this->assertCount(26, app(RegexLoaderStrategy::class)->load());
    }

    /**
     * @test
     *
     * @group Strategy
     * @group Unit
     */
    public function it_can_load_specific_core()
    {
        Config::set('scrubber.regex_loader', [RegexCollection::$GOOGLE_API]);

        $this->assertCount(1, app(RegexLoaderStrategy::class)->load());
    }
}
