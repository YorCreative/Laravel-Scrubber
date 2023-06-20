<?php

namespace YorCreative\Scrubber\Test\Unit\Strategies;

use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers\StringContentHandler;
use YorCreative\Scrubber\Tests\TestCase;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ContentProcessingStrategy;

class ContentProcessingStrategyTest extends TestCase
{
    protected ContentProcessingStrategy $strategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->strategy = $this->app->get(ContentProcessingStrategy::class);
    }

    public function test_it_has_processing_handlers_loaded()
    {
        $this->assertCount(3, $this->strategy->getHandlers());
    }

    public function test_it_can_process_string_content()
    {
        $reflectedStrategy = new \ReflectionMethod(ContentProcessingStrategy::class, 'detectHandlerIndex');

        $reflectedStrategy->setAccessible(true);

        $index = $reflectedStrategy->invokeArgs($this->strategy, [
            'string content'
        ]);

        $this->assertEquals(0, $index);

        $this->assertInstanceOf(StringContentHandler::class, $this->strategy->getHandlers()->get($index));
    }
}
