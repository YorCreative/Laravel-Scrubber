<?php

namespace YorCreative\Scrubber\Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('RegexRepository')]
#[Group('Feature')]
class ScrubLogTest extends TestCase
{
    private array $expectedRecord;

    protected function setUp(): void
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

    public function test_it_can_detect_a_single_piece_of_sensitive_information_and_scrub_the_log()
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
                    'google_api' => app(RegexRepository::class)->getRegexCollection()->get('google_api')->getTestableString(),
                ],
            ],
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }

    public function test_it_can_detect_multiple_sensitive_information_and_scrub_the_log()
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
                            'slack_token' => app(RegexRepository::class)->getRegexCollection()->get('slack_token')
                                ->getTestableString(),
                        ],
                    ],
                ],
                'mailgun_api_key' => app(RegexRepository::class)->getRegexCollection()->get('mailgun_api_key')
                    ->getTestableString(),
            ],
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }

    public function test_it_can_binary_detect_multiple_sensitive_information_and_scrub_the_log()
    {
        $binary = hex2bin('eb13cd61f3e640d1b913eefbb93bd838');

        $this->expectedRecord = array_merge($this->expectedRecord['context'], [
            'some' => 'context',
            'nested' => [
                'randomly' => 'nested',
                'another' => [
                    'nested' => [
                        'message' => config('scrubber.redaction').$binary,
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
                        'message' => 'xoxA-BCDE'.$binary,
                        'array' => [
                            'slack_token' => app(RegexRepository::class)->getRegexCollection()->get('slack_token')
                                ->getTestableString(),
                        ],
                    ],
                ],
                'mailgun_api_key' => app(RegexRepository::class)->getRegexCollection()->get('mailgun_api_key')
                    ->getTestableString(),
            ],
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }

    public function test_it_can_handle_binary_objects()
    {
        $object = new \stdClass;
        $object->a = 1;
        $object->binary = hex2bin('eb13cd61f3e640d1b913eefbb93bd838');

        $this->expectedRecord = array_merge($this->expectedRecord['context'], [
            (array) $object,
        ]);

        $this->record = array_merge($this->record['context'], [
            $object,
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }

    public function test_it_can_handle_resources()
    {
        $resource = fopen('/dev/null', 'r');

        $this->expectedRecord = array_merge($this->expectedRecord['context'], [
            (string) $resource,
        ]);

        $this->record = array_merge($this->record['context'], [
            $resource,
        ]);

        $sanitizedRecord = Scrubber::processMessage($this->record);

        $this->assertEquals($this->expectedRecord, $sanitizedRecord);
    }
}
