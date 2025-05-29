<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies\Handlers;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Services\ScrubberService;
use YorCreative\Scrubber\Strategies\ContentProcessingStrategy\Handlers\ArrayContentHandler;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('Strategy')]
#[Group('Unit')]
class ArrayContentHandlerTest extends TestCase
{
    protected $arrayContentHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arrayContentHandler = new ArrayContentHandler;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that processArray handles JSON encoding failure and still returns an array.
     */
    public function test_process_array_with_invalid_utf8()
    {
        // Create an array with invalid UTF-8 string to cause json_encode to fail
        $invalidUtf8 = "Invalid UTF-8: \xB1\x31";
        $content = ['key' => $invalidUtf8];

        // Process the array
        $processed = $this->arrayContentHandler->processArray($content);

        // Assertions
        $this->assertIsArray($processed, 'The result should be an array.');
        $this->assertArrayHasKey('key', $processed, 'The original key should still exist.');
    }

    /**
     * Test that processArray handles JSON decoding failure after sanitization and returns an array.
     */
    public function test_process_array_with_invalid_json_after_sanitization()
    {
        // Sample content
        $content = ['key' => 'value'];

        // Create a partial mock of ScrubberService
        $mock = Mockery::mock(ScrubberService::class)->makePartial();

        // JSON encoding returns a valid string
        $mock->shouldReceive('encodeRecord')
            ->with($content)
            ->andReturn('{"key":"value"}');

        // Sanitization corrupts the JSON string
        $mock->shouldReceive('autoSanitize')
            ->andReturnUsing(function (&$str) {
                $str = 'invalid_json'; // Invalid JSON to cause json_decode to return null
            });

        // Decoding the invalid JSON returns null
        $mock->shouldReceive('decodeRecord')
            ->with('invalid_json')
            ->andReturn(null);

        // Bind the mock to the Laravel container
        app()->instance(ScrubberService::class, $mock);

        // Process the array with the mock in place
        $processed = $this->arrayContentHandler->processArray($content);

        // Assertions
        $this->assertIsArray($processed, 'The result should be an array even if decoding fails.');
        $this->assertArrayHasKey('key', $processed, 'The original key should still exist.');
    }
}
