<?php

namespace YorCreative\Scrubber\Tests\Feature;

use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Tests\TestCase;

class ScrubLogTest extends TestCase
{
    private array $expectedRecord;

    public function setUp(): void
    {
        parent::setUp();

        $message = 'hello world.';

        $this->expectedRecord = [
            'message' => $message,
            'context' => [],
            'level' => 200,
            'level_name' => 'INFO',
            'datetime' => '2022-08-15T22:12:32.502986+00:00',
            'extra' => [],
        ];
    }

    /**
     * @test
     * @group Feature
     */
    public function it_can_detect_a_single_piece_of_sensitive_information_and_scrub_the_log()
    {
        $this->expectedRecord = array_merge($this->expectedRecord['context'], [
            'some' => 'context',
            'nested' => [
                'randomly' => 'nested',
                'for' => [
                    'testing' => 'test',
                    'google_api' => config('scrubber.redaction'),
                ],
            ],
        ]);

        $this->record = array_merge($this->record['context'], [
            'some' => 'context',
            'nested' => [
                'randomly' => 'nested',
                'for' => [
                    'testing' => 'test',
                    'google_api' => RegexRepository::getRegexCollection()->get('google_api')->getTestableString(),
                ],
            ],
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }

    /**
     * @test
     * @group Feature
     */
    public function it_can_detect_multiple_sensitive_information_and_scrub_the_log()
    {
        $this->expectedRecord = array_merge($this->expectedRecord['context'], [
            'some' => 'context',
            'nested' => [
                'randomly' => 'nested',
                'another' => [
                    'nested' => [
                        'array' => [
                            'slack_token' => config('scrubber.redaction'),
                        ],
                    ],
                ],
                'mailgun_api_key' => config('scrubber.redaction'),
            ],
        ]);

        $this->record = array_merge($this->record['context'], [
            'some' => 'context',
            'nested' => [
                'randomly' => 'nested',
                'another' => [
                    'nested' => [
                        'array' => [
                            'slack_token' => RegexRepository::getRegexCollection()->get('slack_token')
                                ->getTestableString(),
                        ],
                    ],
                ],
                'mailgun_api_key' => RegexRepository::getRegexCollection()->get('mailgun_api_key')
                    ->getTestableString(),
            ],
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }
}
