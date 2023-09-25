<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies;

use Carbon\Carbon;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\ContentProcessingStrategy;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers\ArrayContentHandler;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers\LogRecordContentHandler;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers\StringContentHandler;
use YorCreative\Scrubber\Support\LogRecordFactory;
use YorCreative\Scrubber\Tests\TestCase;

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
            'string content',
        ]);

        $this->assertEquals(0, $index);

        $this->assertInstanceOf(StringContentHandler::class, $this->strategy->getHandlers()->get($index));
    }

    public function test_it_can_process_array_content()
    {
        $reflectedStrategy = new \ReflectionMethod(ContentProcessingStrategy::class, 'detectHandlerIndex');

        $reflectedStrategy->setAccessible(true);

        $index = $reflectedStrategy->invokeArgs($this->strategy, [
            ['key' => 'value'],
        ]);

        $this->assertEquals(1, $index);

        $this->assertInstanceOf(ArrayContentHandler::class, $this->strategy->getHandlers()->get($index));
    }

    public function test_it_can_process_log_record()
    {
        $reflectedStrategy = new \ReflectionMethod(ContentProcessingStrategy::class, 'detectHandlerIndex');

        $reflectedStrategy->setAccessible(true);

        $index = $reflectedStrategy->invokeArgs($this->strategy, [
            LogRecordFactory::buildRecord(
                Carbon::now()->toDateTimeImmutable(),
                'test_channel',
                200,
                'just_a_test',
                [],
                []
            ),
        ]);

        $this->assertEquals(2, $index);

        $this->assertInstanceOf(LogRecordContentHandler::class, $this->strategy->getHandlers()->get($index));
    }
}
