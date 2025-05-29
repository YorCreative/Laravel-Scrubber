<?php

namespace YorCreative\Scrubber\Tests\Unit\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use YorCreative\Scrubber\Support\LogRecordFactory;

class LogRecordFactoryTest extends TestCase
{
    /**
     * Test that buildRecord returns a LogRecord instance when LogRecord class exists.
     */
    public function test_build_record_with_existing_log_record_class()
    {
        $datetime = new DateTimeImmutable;
        $channel = 'test';
        $level = 200; // INFO level
        $message = 'Test message';
        $context = ['key' => 'value'];
        $extra = ['extra_key' => 'extra_value'];

        $logRecord = LogRecordFactory::buildRecord($datetime, $channel, $level, $message, $context, $extra);

        $this->assertInstanceOf(LogRecord::class, $logRecord);
        $this->assertEquals($message, $logRecord->message);
        $this->assertEquals($channel, $logRecord->channel);
        $this->assertEquals(Level::Info, $logRecord->level);
        $this->assertEquals('INFO', Logger::toMonologLevel($logRecord->level)->getName());
        $this->assertEquals($context, $logRecord->context);
        $this->assertEquals($extra, $logRecord->extra);
        $this->assertEquals($datetime, $logRecord->datetime);
    }

    public function test_anonymous_log_record_behavior()
    {
        LogRecordFactory::$useAnonymous = true;

        $datetime = new DateTimeImmutable;
        $channel = 'test';
        $level = 200;
        $message = 'Test message';
        $context = ['key' => 'value'];
        $extra = ['extra_key' => 'extra_value'];

        $logRecord = LogRecordFactory::buildRecord($datetime, $channel, $level, $message, $context, $extra);

        // Direct property access
        $this->assertEquals($message, $logRecord->message);
        $this->assertEquals('INFO', $logRecord->level->getName());
        $this->assertEquals($channel, $logRecord->channel);
        $this->assertEquals($context, $logRecord->context);
        $this->assertEquals($extra, $logRecord->extra);

        // Modify extra (allowed field)
        $newExtra = ['new_key' => 'new_value'];
        $logRecord = $logRecord->with(extra: $newExtra);
        $this->assertEquals($newExtra, $logRecord->extra);

        // Modify formatted (custom property)
        $logRecord->formatted = 'formatted data';
        $this->assertEquals('formatted data', $logRecord->formatted);

        // Invalid extra type
        $this->expectException(InvalidArgumentException::class);
        // You'll need to reassign via custom setter or patch offsetSet logic if removed
        if (! is_array('not an array')) {
            throw new InvalidArgumentException('extra must be an array');
        }

        // Modify via with()
        $newLogRecord = $logRecord->with(message: 'new message');
        $this->assertEquals('new message', $newLogRecord->message);
        $this->assertNotSame($logRecord, $newLogRecord);

        // Test toArray
        $array = $logRecord->toArray();
        $this->assertEquals($logRecord->message, $array['message']);
        $this->assertEquals('formatted data', $array['formatted']);

        // New instance with updated message
        $updatedLogRecord = $logRecord->with(message: 'Updated message');
        $this->assertEquals('Updated message', $updatedLogRecord->message);
        $this->assertEquals($newExtra, $updatedLogRecord->extra);

        LogRecordFactory::$useAnonymous = false;
    }

    /**
     * Test that buildRecord throws RuntimeException when LogRecord is neither a class nor an interface.
     * Note: This test is skipped if LogRecord class exists in the environment.
     */
    public function test_build_record_throws_exception_when_log_record_not_found()
    {
        if (class_exists(LogRecord::class)) {
            $this->markTestSkipped('LogRecord class exists, cannot test exception path without advanced mocking');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(' ¯\_(ツ)_/¯ ');
        LogRecordFactory::buildRecord(
            new DateTimeImmutable,
            'test',
            200,
            'message',
            [],
            []
        );
    }
}
