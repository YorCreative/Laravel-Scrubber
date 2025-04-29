<?php

namespace YorCreative\Scrubber\Tests\Unit\Support;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Support\LogRecordFactory;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('LogRecord')]
#[Group('Unit')]
class LogRecordFactoryTest extends TestCase
{
    public function test_it_can_to_array_log_record()
    {
        $logRecord = LogRecordFactory::buildRecord(
            Carbon::now()->toDateTimeImmutable(),
            'test_channel',
            200,
            'just_a_test',
            [],
            []
        );

        $logRecordArray = $logRecord->toArray();

        $this->assertEquals('INFO', $logRecordArray['level_name']);
        $this->assertEquals('test_channel', $logRecordArray['channel']);
        $this->assertEquals('just_a_test', $logRecordArray['message']);
    }

    public function test_it_can_create_log_record_from_class_to_array(): void
    {
        $buildFromAnonymousLogRecord = new \ReflectionMethod(LogRecordFactory::class, 'buildFromAnonymousLogRecord');

        $buildFromAnonymousLogRecord->setAccessible(true);

        $logRecord = $buildFromAnonymousLogRecord->invokeArgs(new LogRecordFactory, [
            Carbon::now()->toDateTimeImmutable(),
            'test_channel',
            200,
            'just_a_test',
            [],
            [],
        ]);

        $logRecordArray = $logRecord->toArray();

        $this->assertEquals('INFO', $logRecordArray['level_name']);
        $this->assertEquals('test_channel', $logRecordArray['channel']);
        $this->assertEquals('just_a_test', $logRecordArray['message']);
    }
}
