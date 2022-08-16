<?php

namespace YorCreative\Scrubber\Tests;

use YorCreative\Scrubber\ScrubberServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public array $record;

    public function setUp(): void
    {
        parent::setUp();

        $this->record = [
            'message' => 'hello, world.',
            'context' => [],
            'level' => 200,
            'level_name' => 'INFO',
            'datetime' => '2022-08-15T22:12:32.502986+00:00',
            'extra' => [],
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            ScrubberServiceProvider::class,
        ];
    }
}
