<?php

namespace YorCreative\Scrubber\Tests\Unit\RegexCollection;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('RegexCollection')]
#[Group('Unit')]
class NewCredentialPatternsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scrubber.regex_loader', ['*']);
    }

    public static function credentialSamples(): array
    {
        return [
            'openai project key' => [
                'sk-proj-9TjLmQ0aXbR2vN6wYcE4dPfGhK1sZuA7oI3rB5tW8yUqMxVnCgJlDeSh',
                'OpenAiApiKey',
            ],
            'openai legacy key' => [
                'sk-9TjLmQ0aXbR2vN6wYcE4dPfGhK1sZuA7oI3rB5tW8yUqMxVn',
                'OpenAiApiKey',
            ],
            'anthropic api key' => [
                'sk-ant-api03-Kq7ZmR4tV2wXbN9yLcE6dPfGhJ1sAuI3oB5rT8yUqMxVnCgKlDeShFa-QwErTyU',
                'AnthropicApiKey',
            ],
            'stripe secret key' => [
                'sk_live_EXAMPLENOTAREAL01',
                'StripeSecretKey',
            ],
            'stripe restricted key' => [
                'rk_test_EXAMPLENOTAREAL01',
                'StripeSecretKey',
            ],
            'stripe webhook secret' => [
                'whsec_Kq7ZmR4tV2wXbN9yLcE6dPfGhJ1sAuI3',
                'StripeSecretKey',
            ],
            'github classic pat' => [
                'ghp_EXAMPLENOTAREALTOKEN0000000000000000',
                'GithubPersonalAccessToken',
            ],
            'github fine grained pat' => [
                'github_pat_11EXAMPLENOTAREALTOKEN00000000',
                'GithubPersonalAccessToken',
            ],
        ];
    }

    #[DataProvider('credentialSamples')]
    public function test_it_scrubs_the_credential(string $sample, string $expectedPattern)
    {
        $result = Scrubber::test("the value is {$sample} ok");

        $this->assertTrue($result['matched'], "[{$sample}] was not detected at all.");
        $this->assertArrayHasKey(
            $expectedPattern,
            $result['patterns'],
            "[{$sample}] was not matched by {$expectedPattern}."
        );
        $this->assertStringNotContainsString($sample, $result['scrubbed']);
    }

    public function test_openai_key_does_not_match_the_stripe_pattern()
    {
        $result = Scrubber::test('sk-proj-9TjLmQ0aXbR2vN6wYcE4dPfGhK1sZuA7oI3rB5tW8yUqMxVnCgJlDeSh');

        $this->assertArrayHasKey('OpenAiApiKey', $result['patterns']);
        $this->assertArrayNotHasKey('StripeSecretKey', $result['patterns']);
    }

    public function test_stripe_key_does_not_match_the_openai_pattern()
    {
        $result = Scrubber::test('sk_live_EXAMPLENOTAREAL01');

        $this->assertArrayHasKey('StripeSecretKey', $result['patterns']);
        $this->assertArrayNotHasKey('OpenAiApiKey', $result['patterns']);
    }

    public function test_anthropic_key_does_not_match_the_openai_pattern()
    {
        $result = Scrubber::test('sk-ant-api03-Kq7ZmR4tV2wXbN9yLcE6dPfGhJ1sAuI3oB5rT8yUqMxVnCgKlDeShFa-QwErTyU');

        $this->assertArrayHasKey('AnthropicApiKey', $result['patterns']);
        $this->assertArrayNotHasKey('OpenAiApiKey', $result['patterns']);
    }

    public static function lookalikesThatMustNotMatch(): array
    {
        return [
            'disk- identifier vs OpenAI' => ['disk-0a1b2c3d4e5f60718293a4b5c6d7e8f9'],
            'task- identifier vs OpenAI' => ['task-9f2b1c0d3e4a5b6c7d8e9f0a1b2c3d4e'],
            'risk- identifier vs OpenAI' => ['risk-1234567890abcdef1234567890abcdef'],
            'task_live_ vs Stripe' => ['task_live_abcdefghijklmnopqrst'],
            'disk_test_ vs Stripe' => ['disk_test_abcdefghijklmnopqrst'],
            'highp_ vs GitHub' => ['highp_EXAMPLENOTAREALTOKEN0000000000000000'],
            'disk-ant- vs Anthropic' => ['disk-ant-api03-Kq7ZmR4tV2wXbN9yLcE6dPfGhJ1sAuI3oB5rT8yUq'],
        ];
    }

    /**
     * These identifiers embed a credential prefix after a word character. Without a
     * left boundary the patterns match inside them and silently destroy log data.
     *
     * This asserts only that the four new patterns keep their hands off. It cannot
     * assert the sample survives untouched, because the pre-existing TwilioAppSid
     * pattern (AP[a-zA-Z0-9_-]{32}, matched case-insensitively) already claims some
     * of these strings.
     */
    #[DataProvider('lookalikesThatMustNotMatch')]
    public function test_it_does_not_match_credential_lookalikes(string $sample)
    {
        $result = Scrubber::test("the value is {$sample} ok");

        foreach (['OpenAiApiKey', 'AnthropicApiKey', 'StripeSecretKey', 'GithubPersonalAccessToken'] as $pattern) {
            $this->assertArrayNotHasKey(
                $pattern,
                $result['patterns'],
                "[{$sample}] was wrongly matched by {$pattern}."
            );
        }

    }
}
