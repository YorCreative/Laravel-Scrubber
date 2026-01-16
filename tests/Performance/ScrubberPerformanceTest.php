<?php

namespace YorCreative\Scrubber\Tests\Performance;

use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Tests\TestCase;

class ScrubberPerformanceTest extends TestCase
{
    /**
     * Test that scrubbing 1000 simple messages completes in reasonable time.
     */
    public function test_string_scrubbing_performance(): void
    {
        $sensitiveData = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U';

        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            Scrubber::processMessage($sensitiveData);
        }

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            5.0,
            $elapsed,
            "1000 string scrubs took {$elapsed}s, expected < 5s"
        );
    }

    /**
     * Test that scrubbing 1000 array messages completes in reasonable time.
     */
    public function test_array_scrubbing_performance(): void
    {
        $sensitiveData = [
            'user' => 'john@example.com',
            'token' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U',
            'api_key' => 'sk-1234567890abcdef',
        ];

        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            Scrubber::processMessage($sensitiveData);
        }

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            10.0,
            $elapsed,
            "1000 array scrubs took {$elapsed}s, expected < 10s"
        );
    }

    /**
     * Test that scrubbing nested arrays performs acceptably.
     */
    public function test_nested_array_scrubbing_performance(): void
    {
        $sensitiveData = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'secret' => 'ghp_1234567890abcdefghijklmnopqrstuvwxyz',
                    ],
                ],
            ],
            'another' => [
                'email' => 'test@example.com',
            ],
        ];

        $start = microtime(true);

        for ($i = 0; $i < 500; $i++) {
            Scrubber::processMessage($sensitiveData);
        }

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            10.0,
            $elapsed,
            "500 nested array scrubs took {$elapsed}s, expected < 10s"
        );
    }

    /**
     * Test that scrubbing LogRecords performs acceptably.
     */
    public function test_log_record_scrubbing_performance(): void
    {
        $datetime = new \DateTimeImmutable;
        $message = 'User login with token: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U';
        $context = ['ip' => '192.168.1.1'];

        $start = microtime(true);

        for ($i = 0; $i < 500; $i++) {
            $logRecord = $this->getTestLogRecord($datetime, $message, $context);
            Scrubber::processMessage($logRecord);
        }

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            10.0,
            $elapsed,
            "500 log record scrubs took {$elapsed}s, expected < 10s"
        );
    }
}
