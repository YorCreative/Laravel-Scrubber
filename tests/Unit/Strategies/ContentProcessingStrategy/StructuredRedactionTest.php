<?php

namespace YorCreative\Scrubber\Tests\Unit\Strategies\ContentProcessingStrategy;

use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Scrubber;
use YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy;
use YorCreative\Scrubber\Support\LogRecordFactory;
use YorCreative\Scrubber\Tests\Fixtures\BinaryMixedVisibility;
use YorCreative\Scrubber\Tests\Fixtures\BinaryPayloadDto;
use YorCreative\Scrubber\Tests\Fixtures\MixedVisibility;
use YorCreative\Scrubber\Tests\Fixtures\SecretiveDto;
use YorCreative\Scrubber\Tests\Fixtures\StringableThing;
use YorCreative\Scrubber\Tests\TestCase;

/**
 * Redaction of structured (array / LogRecord) content.
 *
 * Array content used to be JSON-encoded, matched against the plaintext patterns
 * as one blob, and decoded again. JSON escaping breaks that in two ways:
 *
 *  - a literal backslash in a secret becomes two characters, so the quoted
 *    plaintext pattern no longer matches the encoded form; and
 *  - a leading newline/tab/CR becomes "\n" / "\t" / "\r", putting the word
 *    characters n, t and r immediately before the credential -- which defeats
 *    the (?<![A-Za-z0-9_]) left boundary every provider pattern relies on.
 *
 * Both leak real credentials through structured logs, which is the shape almost
 * all production logging actually uses.
 */
#[Group('ContentProcessingStrategy')]
#[Group('Unit')]
class StructuredRedactionTest extends TestCase
{
    private const REDACTION = '**redacted**';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scrubber.regex_loader', ['*']);
    }

    protected function bootScrubberWithGitlabSecret(string $secret): void
    {
        Config::set('scrubber.secret_manager.enabled', true);
        Config::set('scrubber.secret_manager.providers.gitlab.enabled', true);

        $this->createGitlabClientMock([
            new Response(200, [], json_encode([
                [
                    'variable_type' => 'env_var',
                    'key' => 'structured_secret',
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

    protected function bootScrubberWithConfigSecret(string $value): void
    {
        Config::set('scrubber.config_loader', ['app.structured_literal']);
        Config::set('app.structured_literal', $value);
        Config::set('scrubber.regex_loader', ['*']);

        app()->forgetInstance(RegexRepository::class);
        app()->forgetInstance(RegexLoaderStrategy::class);
    }

    /**
     * Walk every scalar in a nested structure and assert the needle is absent.
     */
    protected function assertNoScalarContains(array $haystack, string $needle, string $message): void
    {
        array_walk_recursive($haystack, function ($value) use ($needle, $message) {
            if (is_string($value)) {
                $this->assertStringNotContainsString($needle, $value, $message);
            }
        });
    }

    // ---------------------------------------------------------------------
    // Managed secrets containing backslashes
    // ---------------------------------------------------------------------

    public function test_it_redacts_a_backslash_secret_in_a_plain_string()
    {
        $secret = 'back\\slash\\secret';
        $this->bootScrubberWithGitlabSecret($secret);

        $scrubbed = Scrubber::processMessage("value is {$secret} done");

        $this->assertStringNotContainsString($secret, $scrubbed);
    }

    public function test_it_redacts_a_backslash_secret_in_a_flat_array()
    {
        $secret = 'back\\slash\\secret';
        $this->bootScrubberWithGitlabSecret($secret);

        $scrubbed = Scrubber::processMessage(['token' => "value is {$secret} done"]);

        $this->assertIsArray($scrubbed);
        $this->assertNoScalarContains($scrubbed, $secret, 'Backslash secret leaked through a flat array.');
    }

    public function test_it_redacts_a_backslash_secret_in_a_nested_array()
    {
        $secret = 'back\\slash\\secret';
        $this->bootScrubberWithGitlabSecret($secret);

        $scrubbed = Scrubber::processMessage([
            'outer' => [
                'inner' => [
                    'token' => $secret,
                ],
                'sibling' => 'nothing sensitive',
            ],
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertNoScalarContains($scrubbed, $secret, 'Backslash secret leaked through a nested array.');
        $this->assertSame('nothing sensitive', $scrubbed['outer']['sibling']);
    }

    public function test_it_redacts_a_backslash_secret_in_monolog_context()
    {
        $secret = 'back\\slash\\secret';
        $this->bootScrubberWithGitlabSecret($secret);

        $record = LogRecordFactory::buildRecord(
            new \DateTimeImmutable('2022-08-15T22:12:32+00:00'),
            'testing',
            Level::Info->value,
            'a message',
            ['credential' => $secret, 'nested' => ['deep' => $secret]],
            []
        );

        $scrubbed = Scrubber::processMessage($record);

        $this->assertInstanceOf(LogRecord::class, $scrubbed);
        $this->assertNoScalarContains(
            $scrubbed->context,
            $secret,
            'Backslash secret leaked through Monolog context.'
        );
    }

    // ---------------------------------------------------------------------
    // Configured literal secrets with awkward characters
    // ---------------------------------------------------------------------

    public static function awkwardConfigLiterals(): array
    {
        return [
            'backslashes' => ['conf\\back\\slash\\value'],
            'double quotes' => ['conf"quoted"value'],
            'single quotes' => ["conf'quoted'value"],
            'newline' => ["conf\nnewline\nvalue"],
            'tab' => ["conf\ttab\tvalue"],
            'carriage return' => ["conf\rcr\rvalue"],
            'mixed' => ["conf\\\"mixed\n\tvalue"],
        ];
    }

    #[DataProvider('awkwardConfigLiterals')]
    public function test_it_redacts_configured_literal_secrets_in_structured_content(string $value)
    {
        $this->bootScrubberWithConfigSecret($value);

        $scrubbed = Scrubber::processMessage([
            'outer' => ['inner' => "prefix {$value} suffix"],
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertNoScalarContains(
            $scrubbed,
            $value,
            'Configured literal leaked through structured content.'
        );
    }

    // ---------------------------------------------------------------------
    // Provider credentials after escaped whitespace
    // ---------------------------------------------------------------------

    public static function credentialsAfterWhitespace(): array
    {
        $credentials = [
            'openai' => 'sk-proj-9TjLmQ0aXbR2vN6wYcE4dPfGhK1sZuA7oI3rB5tW8yUqMxVnCgJlDeSh',
            'anthropic' => 'sk-ant-api03-Kq7ZmR4tV2wXbN9yLcE6dPfGhJ1sAuI3oB5rT8yUqMxVnCgKlDeShFa-QwErTyU',
            'stripe' => 'sk_live_EXAMPLENOTAREAL01',
            'github' => 'ghp_EXAMPLENOTAREALTOKEN0000000000000000',
        ];

        $whitespace = [
            'newline' => "\n",
            'tab' => "\t",
            'carriage return' => "\r",
        ];

        $cases = [];
        foreach ($credentials as $provider => $token) {
            foreach ($whitespace as $wsName => $ws) {
                $cases["{$provider} after {$wsName}"] = [$token, $ws];
            }
        }

        return $cases;
    }

    #[DataProvider('credentialsAfterWhitespace')]
    public function test_it_redacts_provider_credentials_after_escaped_whitespace(string $token, string $whitespace)
    {
        $scrubbed = Scrubber::processMessage([
            'log' => "some leading text{$whitespace}{$token}",
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertNoScalarContains(
            $scrubbed,
            $token,
            'Credential leaked when preceded by escaped whitespace in structured content.'
        );
    }

    /**
     * A partial match is still a breach: a pattern that consumes only the prefix
     * leaves the entropy-bearing tail of the credential in the log. Assert the
     * distinctive tail is gone too, not merely the full token string.
     */
    #[DataProvider('credentialsAfterWhitespace')]
    public function test_it_redacts_the_whole_credential_payload(string $token, string $whitespace)
    {
        $tail = substr($token, -16);

        $scrubbed = Scrubber::processMessage([
            'log' => "some leading text{$whitespace}{$token}",
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertNoScalarContains(
            $scrubbed,
            $tail,
            'The tail of the credential survived redaction; only the prefix was consumed.'
        );
        $this->assertStringContainsString(
            self::REDACTION,
            $scrubbed['log'],
            'The redaction marker is missing, so nothing was actually replaced.'
        );
        $this->assertStringContainsString(
            'some leading text',
            $scrubbed['log'],
            'Surrounding log text must survive redaction.'
        );
    }

    // ---------------------------------------------------------------------
    // Lookalikes and collisions must still be respected in structured content
    // ---------------------------------------------------------------------

    public static function lookalikesThatMustSurvive(): array
    {
        return [
            'disk- identifier' => ['disk-0a1b2c3d4e5f60718293a4b5c6d7e8f9'],
            'task_live_ identifier' => ['task_live_abcdefghijklmnopqrst'],
            'highp_ identifier' => ['highp_EXAMPLENOTAREALTOKEN0000000000000000'],
        ];
    }

    #[DataProvider('lookalikesThatMustSurvive')]
    public function test_it_leaves_lookalikes_untouched_in_structured_content(string $sample)
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

    public function test_lookalikes_after_escaped_whitespace_are_still_not_matched()
    {
        // The fix must not be "drop the left boundary" -- that would redact these.
        foreach (self::lookalikesThatMustSurvive() as [$sample]) {
            $result = Scrubber::test("leading\n{$sample}");

            foreach (['OpenAiApiKey', 'AnthropicApiKey', 'StripeSecretKey', 'GithubPersonalAccessToken'] as $pattern) {
                $this->assertArrayNotHasKey(
                    $pattern,
                    $result['patterns'],
                    "[{$sample}] after a newline was wrongly matched by {$pattern}."
                );
            }
        }
    }

    // ---------------------------------------------------------------------
    // Structural integrity
    // ---------------------------------------------------------------------

    public function test_it_preserves_scalar_types_and_structure()
    {
        $scrubbed = Scrubber::processMessage([
            'int' => 42,
            'float' => 1.5,
            'true' => true,
            'false' => false,
            'null' => null,
            'string' => 'plain',
            'nested' => ['int' => 7, 'list' => [1, 2, 3]],
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(42, $scrubbed['int'], 'Integers must not be coerced to strings.');
        $this->assertSame(1.5, $scrubbed['float'], 'Floats must not be coerced to strings.');
        $this->assertTrue($scrubbed['true'], 'Booleans must not be coerced.');
        $this->assertFalse($scrubbed['false'], 'Booleans must not be coerced.');
        $this->assertNull($scrubbed['null']);
        $this->assertSame('plain', $scrubbed['string']);
        $this->assertSame(7, $scrubbed['nested']['int']);
        $this->assertSame([1, 2, 3], $scrubbed['nested']['list']);
    }

    public function test_it_still_redacts_a_credential_stored_as_a_number()
    {
        // Non-string scalars are still scanned. A card number or account id is
        // often stored as an int, and skipping non-strings for the sake of type
        // preservation would silently stop redacting it.
        $scrubbed = Scrubber::processMessage([
            'card_int' => 4111111111111111,
            'card_str' => '4111111111111111',
            'untouched' => 42,
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertStringNotContainsString('4111111111111111', (string) $scrubbed['card_int']);
        $this->assertStringNotContainsString('4111111111111111', (string) $scrubbed['card_str']);
        $this->assertSame(42, $scrubbed['untouched'], 'An untouched number keeps its type.');
    }

    public function test_redacting_a_number_leaves_the_record_valid()
    {
        // Replacing a bare JSON number with the redaction marker used to produce
        // invalid JSON, so json_decode() failed and every scalar in the record was
        // stringified by the fallback path.
        $scrubbed = Scrubber::processMessage([
            'card' => 4111111111111111,
            'keep_int' => 7,
            'keep_bool' => true,
            'keep_float' => 2.5,
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(7, $scrubbed['keep_int'], 'A redaction elsewhere must not stringify other scalars.');
        $this->assertTrue($scrubbed['keep_bool']);
        $this->assertSame(2.5, $scrubbed['keep_float']);

        $json = json_encode($scrubbed);
        $this->assertIsString($json);
        $this->assertIsArray(json_decode($json, true), 'The redacted record must remain valid JSON.');
    }

    public function test_redacted_structured_content_remains_serializable()
    {
        $scrubbed = Scrubber::processMessage([
            'log' => "leading\nsk_live_EXAMPLENOTAREAL01",
            'nested' => ['deep' => "tabbed\tghp_EXAMPLENOTAREALTOKEN0000000000000000"],
        ]);

        $this->assertIsArray($scrubbed);

        $json = json_encode($scrubbed);
        $this->assertIsString($json, 'Redacted content must still encode to JSON.');
        $this->assertNotFalse($json);
        $this->assertIsArray(
            json_decode($json, true),
            'Redacted content must round-trip through JSON.'
        );
    }

    // -----------------------------------------------------------------
    // Object serialization contracts
    // -----------------------------------------------------------------

    public function test_it_honours_json_serializable_and_does_not_expose_private_state()
    {
        // The DTO's JSON contract exposes only `name`. Walking the object with a
        // raw (array) cast would ignore jsonSerialize() entirely and surface the
        // private password under a null-byte-mangled key.
        $scrubbed = Scrubber::processMessage([
            'dto' => new SecretiveDto('alice', 'hunter2-secret'),
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(['name' => 'alice'], $scrubbed['dto']);

        $encoded = json_encode($scrubbed);
        $this->assertStringNotContainsString('hunter2-secret', $encoded);
        $this->assertStringNotContainsString('password', $encoded);
    }

    public function test_it_does_not_expose_private_or_protected_properties()
    {
        $scrubbed = Scrubber::processMessage([
            'obj' => new MixedVisibility,
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(['pub' => 'public-value'], $scrubbed['obj']);

        $encoded = json_encode($scrubbed);
        $this->assertStringNotContainsString('protected-value', $encoded);
        $this->assertStringNotContainsString('PRIVATEVALUE', $encoded);
        // json_encode() renders the null-byte prefixes a raw (array) cast puts on
        // non-public property names as the escape sequence \u0000, so that escaped
        // form is what has to be absent here -- a raw NUL never survives encoding.
        $this->assertStringNotContainsString('\u0000', $encoded);
    }

    public function test_a_stringable_object_still_follows_its_json_representation()
    {
        $scrubbed = Scrubber::processMessage([
            'thing' => new StringableThing,
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(['shown' => 'shown-value'], $scrubbed['thing']);
        $this->assertStringNotContainsString('hidden-value', json_encode($scrubbed));
    }

    public function test_an_eloquent_model_stays_a_structured_array()
    {
        $model = new class extends Model
        {
            protected $guarded = [];
        };
        $model->setRawAttributes([
            'id' => 7,
            'note' => 'sk_live_EXAMPLENOTAREAL01',
        ]);

        $scrubbed = Scrubber::processMessage(['model' => $model]);

        $this->assertIsArray($scrubbed);
        $this->assertIsArray(
            $scrubbed['model'],
            'An Eloquent model must stay a structured array, not collapse to a JSON string.'
        );
        $this->assertSame(7, $scrubbed['model']['id']);
        $this->assertStringNotContainsString('sk_live_EXAMPLENOTAREAL01', $scrubbed['model']['note']);
    }

    public function test_it_honours_json_serializable_when_encoding_fails()
    {
        // json_encode() rejects the invalid UTF-8 the serializer returns. The
        // fallback must still be the serializer's representation -- reaching for
        // the object's raw properties there would publish the public field
        // jsonSerialize() deliberately withheld.
        $scrubbed = Scrubber::processMessage([
            'dto' => new BinaryPayloadDto,
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertArrayNotHasKey(
            'password',
            $scrubbed['dto'],
            'The JSON-error fallback published a field the serializer excluded.'
        );
        $this->assertArrayHasKey(
            'payload',
            $scrubbed['dto'],
            "The serializer's own representation was discarded on the failure path."
        );
        $this->assertStringNotContainsString(
            'excluded-public-password',
            var_export($scrubbed, true)
        );
    }

    public function test_the_encoding_failure_fallback_still_hides_non_public_state()
    {
        // Same failure path, but for an object with no JSON contract: the
        // representation is its public properties, and nothing more.
        $scrubbed = Scrubber::processMessage([
            'obj' => new BinaryMixedVisibility,
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertArrayHasKey('binary', $scrubbed['obj']);
        $this->assertSame(
            "\xB1",
            $scrubbed['obj']['binary'],
            'A binary value must survive the encoding-failure fallback intact.'
        );
        $this->assertArrayNotHasKey('prot', $scrubbed['obj']);
        $this->assertArrayNotHasKey('priv', $scrubbed['obj']);
        $this->assertStringNotContainsString('PRIVATEVALUE', var_export($scrubbed, true));
    }

    public function test_nested_objects_inside_arrays_keep_their_contracts()
    {
        $scrubbed = Scrubber::processMessage([
            'outer' => [
                'inner' => new SecretiveDto('bob', 'sk_live_EXAMPLENOTAREAL01'),
            ],
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(['name' => 'bob'], $scrubbed['outer']['inner']);
        $this->assertStringNotContainsString('sk_live_EXAMPLENOTAREAL01', json_encode($scrubbed));
    }

    // -----------------------------------------------------------------
    // Keys of every type are scanned
    // -----------------------------------------------------------------

    public function test_it_redacts_a_credential_used_as_a_numeric_key()
    {
        // PHP silently casts a numeric-string key to an integer, so a key-based
        // scan that only looks at string keys lets a card number through whole.
        $scrubbed = Scrubber::processMessage([
            '4111111111111111' => 'value-under-card-key',
        ]);

        $this->assertIsArray($scrubbed);
        $key = (string) array_key_first($scrubbed);

        $this->assertStringNotContainsString(
            '4111111111111111',
            $key,
            'A credential used as a numeric key survived redaction.'
        );
    }

    public function test_it_redacts_a_credential_used_as_a_string_key()
    {
        $scrubbed = Scrubber::processMessage([
            'sk_live_EXAMPLENOTAREAL01' => 'value-under-sensitive-key',
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertStringNotContainsString(
            'sk_live_EXAMPLENOTAREAL01',
            (string) array_key_first($scrubbed)
        );
    }

    public function test_untouched_keys_keep_their_original_type()
    {
        $scrubbed = Scrubber::processMessage([
            0 => 'first',
            7 => 'second',
            'named' => 'third',
        ]);

        $this->assertIsArray($scrubbed);
        $this->assertSame(
            ['integer', 'integer', 'string'],
            array_map('gettype', array_keys($scrubbed)),
            'Keys that were not redacted must keep their original type.'
        );
        $this->assertSame('second', $scrubbed[7]);
    }

    public function test_it_preserves_monolog_record_metadata()
    {
        $record = LogRecordFactory::buildRecord(
            new \DateTimeImmutable('2022-08-15T22:12:32+00:00'),
            'a-channel',
            Level::Warning->value,
            "leading\nsk_live_EXAMPLENOTAREAL01",
            ['k' => 'v'],
            ['extra_key' => 'extra_value']
        );

        $scrubbed = Scrubber::processMessage($record);

        $this->assertInstanceOf(LogRecord::class, $scrubbed);
        $this->assertSame('a-channel', $scrubbed->channel);
        $this->assertSame(Level::Warning, $scrubbed->level);
        $this->assertSame(['extra_key' => 'extra_value'], $scrubbed->extra);
        $this->assertStringNotContainsString('sk_live_EXAMPLENOTAREAL01', $scrubbed->message);
    }
}
