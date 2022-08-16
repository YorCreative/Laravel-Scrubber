<?php

namespace YorCreative\Scrubber\Tests\Feature;

use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Tests\TestCase;

class ScrubMessageTest extends TestCase
{
    /**
     * @test
     * @group Feature
     */
    public function it_can_detect_a_single_piece_of_sensitive_data_and_sanitize_it()
    {
        $message = 'Something something, here is the slack token {slack_token}';

        $expected = str_replace('{slack_token}', config('scrubber.redaction'), $message);

        $message = str_replace(
            '{slack_token}',
            RegexRepository::getRegexCollection()->get('slack_token')->getTestableString(),
            $message
        );

        $sanitizedMessage = Scrubber::processMessage($message);

        $this->assertEquals($expected, $sanitizedMessage);
    }

    /**
     * @test
     * @group Feature
     */
    public function it_can_detect_a_multiple_pieces_of_sensitive_data_and_sanitize_them()
    {
        $message = 'here is the slack token {slack_token} and the google api token {google_api}';

        $expected = str_replace('{slack_token}', config('scrubber.redaction'), $message);
        $expected = str_replace('{google_api}', config('scrubber.redaction'), $expected);

        $message = str_replace(
            '{slack_token}',
            RegexRepository::getRegexCollection()->get('slack_token')->getTestableString(),
            $message
        );

        $message = str_replace(
            '{google_api}',
            RegexRepository::getRegexCollection()->get('google_api')->getTestableString(),
            $message
        );

        $sanitizedMessage = Scrubber::processMessage($message);

        $this->assertEquals($expected, $sanitizedMessage);
    }
}
