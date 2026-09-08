<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies\RegexLoader;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('RegexLoader')]
#[Group('Unit')]
class MetacharacterScrubbingTest extends TestCase
{
    public static function secretsContainingRegexMetacharacters(): array
    {
        return [
            'plus sign' => ['sk-proj-AbC+dEf12345'],
            'parentheses' => ['pw(test)123'],
            'square brackets' => ['secret[a]b'],
            'question mark' => ['what?ever123'],
            'asterisk' => ['star*key*value'],
            'tilde (regex delimiter)' => ['tilde~secret~value'],
            'backslash' => ['back\\slash\\secret'],
            'dot and caret' => ['^v1.2.3$'],
            'alphanumeric control' => ['plainSecret123'],
        ];
    }

    #[DataProvider('secretsContainingRegexMetacharacters')]
    public function test_it_scrubs_secrets_containing_regex_metacharacters(string $secret)
    {
        $this->bootScrubberWithGitlabSecret($secret);

        $scrubbed = Scrubber::processMessage("config value is {$secret} and nothing else");

        $this->assertStringNotContainsString(
            $secret,
            $scrubbed,
            "The secret [{$secret}] was not redacted."
        );
    }

    protected function bootScrubberWithGitlabSecret(string $secret): void
    {
        config()->set('scrubber.secret_manager.enabled', true);
        config()->set('scrubber.secret_manager.providers.gitlab.enabled', true);

        $this->createGitlabClientMock([
            new Response(200, [], json_encode([
                [
                    'variable_type' => 'env_var',
                    'key' => 'metacharacter_secret',
                    'value' => $secret,
                    'protected' => false,
                    'masked' => true,
                    'environment_scope' => '*',
                ],
            ])),
        ]);

        app()->forgetInstance(RegexRepository::class);
        app()->forgetInstance(RegexLoaderStrategy::class);
    }

    public static function configValuesContainingRegexMetacharacters(): array
    {
        return [
            'tilde (regex delimiter)' => ['pa~ss~word~value'],
            'plus sign' => ['conf+value+123'],
            'parentheses' => ['conf(value)123'],
            'alphanumeric control' => ['plainConfig123'],
        ];
    }

    #[DataProvider('configValuesContainingRegexMetacharacters')]
    public function test_it_scrubs_config_values_containing_regex_metacharacters(string $value)
    {
        config()->set('scrubber.config_loader', ['app.scrubber_test_value']);
        config()->set('app.scrubber_test_value', $value);
        config()->set('scrubber.regex_loader', ['*']);

        app()->forgetInstance(RegexRepository::class);
        app()->forgetInstance(RegexLoaderStrategy::class);

        $scrubbed = Scrubber::processMessage("config value is {$value} and nothing else");

        $this->assertStringNotContainsString(
            $value,
            $scrubbed,
            "The config value [{$value}] was not redacted."
        );
    }

    public function test_a_loaded_secret_exposes_its_plaintext_as_the_testable_string()
    {
        // scrubber:validate matches a pattern against its testable string. Secrets
        // hold ciphertext, so the testable string has to be the decrypted value or
        // every secret would report as failing validation.
        $secret = 'super~secret+value';
        $this->bootScrubberWithGitlabSecret($secret);

        $regexClass = app(RegexRepository::class)
            ->getRegexCollection()
            ->first(fn ($class) => $class->isSecret());

        $this->assertNotNull($regexClass, 'No secret-backed regex class was loaded.');
        $this->assertTrue($regexClass->isSecret());
        $this->assertSame($secret, $regexClass->getTestableString());
        $this->assertNotSame($secret, $regexClass->getPattern(), 'The stored pattern should remain ciphertext.');
    }

    public function test_it_logs_and_continues_when_secret_loading_fails()
    {
        config()->set('scrubber.secret_manager.enabled', true);
        config()->set('scrubber.secret_manager.providers.gitlab.enabled', true);

        $this->createGitlabClientMock([
            new Response(500, [], 'upstream exploded'),
        ]);

        app()->forgetInstance(RegexRepository::class);
        app()->forgetInstance(RegexLoaderStrategy::class);

        Log::shouldReceive('warning')->atLeast()->once();

        // Loading must not throw; the remaining core patterns still apply.
        $scrubbed = Scrubber::processMessage('nothing sensitive here');

        $this->assertSame('nothing sensitive here', $scrubbed);
    }

    public function test_a_loaded_config_value_exposes_its_raw_value_as_the_testable_string()
    {
        // scrubber:validate matches the stored pattern against the testable string.
        // Returning the quoted pattern here makes every metacharacter-bearing config
        // value report as failing and the command exit non-zero.
        $value = 'super~\\d.*secret';
        config()->set('scrubber.config_loader', ['app.validate_me']);
        config()->set('app.validate_me', $value);
        config()->set('scrubber.regex_loader', ['*']);

        app()->forgetInstance(RegexRepository::class);
        app()->forgetInstance(RegexLoaderStrategy::class);

        $regexClass = app(RegexRepository::class)->getRegexCollection()->get('config::app.validate_me');

        $this->assertNotNull($regexClass, 'The config value was not loaded.');
        $this->assertSame($value, $regexClass->getTestableString());
        $this->assertSame(preg_quote($value), $regexClass->getPattern());

        // What scrubber:validate actually does.
        $compiled = '~'.str_replace('~', '\\~', $regexClass->getPattern()).'~Si';
        $this->assertSame(1, preg_match($compiled, $regexClass->getTestableString()));
    }
}
