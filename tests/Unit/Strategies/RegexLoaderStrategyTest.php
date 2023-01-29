<?php

namespace YorCreative\Scrubber\Test\Unit\Strategies;

use Illuminate\Support\Collection;
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
        $regexClasses = new Collection();
        app(RegexLoaderStrategy::class)->load($regexClasses);

        $this->assertCount(25, $regexClasses);
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
        $regexClasses = new Collection();
        app(RegexLoaderStrategy::class)->load($regexClasses);

        $this->assertCount(1, $regexClasses);
    }
}
