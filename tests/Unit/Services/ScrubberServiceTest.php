<?php

namespace YorCreative\Scrubber\Tests\Unit\Services;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Services\ScrubberService;
use YorCreative\Scrubber\Tests\TestCase;

class ScrubberServiceTest extends TestCase
{

    #[Group('ScrubberService')]
    #[Group('Unit')]
    public function test_it_can_encode_a_record()
    {
        $this->assertIsString(ScrubberService::encodeRecord($this->record));
    }

    #[Group('ScrubberService')]
    #[Group('Unit')]
    public function test_it_can_decode_an_encoded_record()
    {
        $this->assertIsArray(ScrubberService::decodeRecord('{"test": "test"}'));
    }

    #[Group('ScrubberService')]
    #[Group('Unit')]
    public function test_it_can_auto_sanitize_a_record()
    {
        $content = json_encode(array_merge($this->record['context'], [
            'some' => 'context',
            'nested' => [
                'randomly' => 'nested',
                'array' => [
                    'testing' => 'test',
                    'google_api' => app(RegexRepository::class)->getRegexCollection()->get('google_api')->getTestableString(),
                ],
            ],
        ]));

        ScrubberService::autoSanitize($content);

        $this->assertStringContainsString(config('scrubber.redaction'), $content);
    }

    public function test_it_can_get_regex_repository()
    {
        $this->assertInstanceOf(RegexRepository::class, ScrubberService::getRegexRepository());
    }

    public function test_it_can_handle_get_replacement_value_on_custom_class()
    {
        $withReplacement = new class implements RegexCollectionInterface
        {
            public function isSecret(): bool
            {
                return false;
            }

            public function getPattern(): string
            {
                return 'something_with';
            }

            public function getTestableString(): string
            {
                return 'something_with';
            }

            public function getReplacementValue(): string
            {
                return 'not_something';
            }
        };

        $withoutReplacement = new class implements RegexCollectionInterface
        {
            public function isSecret(): bool
            {
                return false;
            }

            public function getPattern(): string
            {
                return 'without_something';
            }

            public function getTestableString(): string
            {
                return 'without_something';
            }
        };

        $regexCollection = collect([
            'with_replacement' => $withReplacement,
            'without_replacement' => $withoutReplacement,
        ]);

        $regexRepository = new RegexRepository($regexCollection);
        $this->app->instance(RegexRepository::class, $regexRepository);

        $content = 'something_with';
        ScrubberService::autoSanitize($content);
        $this->assertEquals($withReplacement->getReplacementValue(), $content);

        $content = 'without_something';
        ScrubberService::autoSanitize($content);

        $defaultReplacementValue = config('scrubber.redaction');
        $this->assertEquals($defaultReplacementValue, $content);

        $this->assertNotEquals(
            $withReplacement->getReplacementValue(),
            $defaultReplacementValue
        );
    }
}
