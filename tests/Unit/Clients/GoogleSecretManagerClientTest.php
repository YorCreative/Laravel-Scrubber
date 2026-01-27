<?php

namespace YorCreative\Scrubber\Tests\Unit\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Clients\GoogleSecretManagerClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('GoogleSecretManagerClient')]
#[Group('Unit')]
class GoogleSecretManagerClientTest extends TestCase
{
    protected array $requestHistory = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scrubber.secret_manager.providers.google.project_id', 'test-project');
        config()->set('scrubber.secret_manager.providers.google.access_token', 'test-access-token');

        $this->requestHistory = [];
    }

    protected function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        // Add history middleware to track requests
        $history = Middleware::history($this->requestHistory);
        $handlerStack->push($history);

        return new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://secretmanager.googleapis.com/v1',
        ]);
    }

    // ==================== listSecrets() Tests ====================

    public function test_list_secrets_returns_array_of_secrets(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'secrets' => [
                    ['name' => 'projects/test-project/secrets/secret1'],
                    ['name' => 'projects/test-project/secrets/secret2'],
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(2, $secrets);
        $this->assertEquals('secret1', $secrets[0]['name']);
        $this->assertEquals('secret2', $secrets[1]['name']);
    }

    public function test_list_secrets_includes_project_id_in_path(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['secrets' => []])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $client->listSecrets();

        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/projects/test-project/secrets', $request->getUri()->getPath());
    }

    public function test_list_secrets_handles_pagination(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'secrets' => [
                    ['name' => 'projects/test-project/secrets/secret1'],
                ],
                'nextPageToken' => 'page2token',
            ])),
            new Response(200, [], json_encode([
                'secrets' => [
                    ['name' => 'projects/test-project/secrets/secret2'],
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(2, $secrets);
        $this->assertEquals('secret1', $secrets[0]['name']);
        $this->assertEquals('secret2', $secrets[1]['name']);

        // Verify two requests were made
        $this->assertCount(2, $this->requestHistory);

        // Verify second request includes page token
        $secondRequest = $this->requestHistory[1]['request'];
        $this->assertStringContainsString('pageToken=page2token', $secondRequest->getUri()->getQuery());
    }

    public function test_list_secrets_handles_empty_response(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['secrets' => []])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(0, $secrets);
        $this->assertIsArray($secrets);
    }

    public function test_list_secrets_handles_missing_secrets_key(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([])), // No 'secrets' key
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(0, $secrets);
    }

    public function test_list_secrets_throws_on_http_error(): void
    {
        $mockClient = $this->createMockClient([
            new Response(403, [], json_encode([
                'error' => ['code' => 403, 'message' => 'Permission denied'],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to list Google Cloud secrets');

        $client->listSecrets();
    }

    public function test_list_secrets_throws_on_network_error(): void
    {
        $mockClient = $this->createMockClient([
            new RequestException(
                'Connection timed out',
                new Request('GET', '/projects/test-project/secrets')
            ),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to list Google Cloud secrets');

        $client->listSecrets();
    }

    // ==================== getSecretValue() Tests ====================

    public function test_get_secret_value_returns_decoded_secret(): void
    {
        $secretValue = 'super-secret-value';
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'name' => 'projects/test-project/secrets/my-secret/versions/latest',
                'payload' => [
                    'data' => base64_encode($secretValue),
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('my-secret');

        $this->assertEquals('my-secret', $secret['name']);
        $this->assertEquals($secretValue, $secret['value']);
    }

    public function test_get_secret_value_decodes_base64(): void
    {
        $originalValue = 'This is a secret with special chars: @#$%^&*()';
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => [
                    'data' => base64_encode($originalValue),
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('test-secret');

        $this->assertEquals($originalValue, $secret['value']);
    }

    public function test_get_secret_value_with_specific_version(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => ['data' => base64_encode('versioned')],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('my-secret', '5');

        // Verify the URL includes the version
        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/versions/5:access', $request->getUri()->getPath());
    }

    public function test_get_secret_value_defaults_to_latest_version(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => ['data' => base64_encode('latest')],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $client->getSecretValue('my-secret');

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/versions/latest:access', $request->getUri()->getPath());
    }

    public function test_get_secret_value_handles_missing_payload(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                // No 'payload' key
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('empty-secret');

        $this->assertEquals('', $secret['value']);
    }

    public function test_get_secret_value_handles_missing_data_in_payload(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => [], // No 'data' key
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('empty-secret');

        $this->assertEquals('', $secret['value']);
    }

    public function test_get_secret_value_handles_padded_base64(): void
    {
        // Test properly padded base64 with special characters in the original
        $original = "line1\nline2\ttab";
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => [
                    'data' => base64_encode($original),
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('padded-secret');

        $this->assertEquals($original, $secret['value']);
    }

    public function test_get_secret_value_throws_on_not_found(): void
    {
        $mockClient = $this->createMockClient([
            new Response(404, [], json_encode([
                'error' => ['code' => 404, 'message' => 'Secret not found'],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to get Google Cloud secret');

        $client->getSecretValue('nonexistent');
    }

    public function test_get_secret_value_throws_on_forbidden(): void
    {
        $mockClient = $this->createMockClient([
            new Response(403, [], json_encode([
                'error' => ['code' => 403, 'message' => 'Permission denied'],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);

        $this->expectException(SecretProviderException::class);

        $client->getSecretValue('forbidden-secret');
    }

    // ==================== Authentication Tests ====================

    public function test_throws_when_no_auth_configured(): void
    {
        config()->set('scrubber.secret_manager.providers.google.access_token', null);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Google Cloud Secret Manager authentication not configured');

        new GoogleSecretManagerClient;
    }

    public function test_uses_direct_access_token_when_provided(): void
    {
        config()->set('scrubber.secret_manager.providers.google.access_token', 'my-direct-token');

        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['secrets' => []])),
        ]);

        // Should not throw - direct token is used
        $client = new GoogleSecretManagerClient($mockClient);
        $client->listSecrets();

        $this->assertTrue(true); // If we got here, auth succeeded
    }

    // ==================== JSON Handling Tests ====================

    public function test_get_secret_handles_json_secret_value(): void
    {
        $jsonValue = '{"username":"admin","password":"secret123"}';
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => [
                    'data' => base64_encode($jsonValue),
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('json-secret');

        // The client should return the raw JSON string (provider handles parsing)
        $this->assertEquals($jsonValue, $secret['value']);
    }

    public function test_get_secret_handles_binary_data(): void
    {
        $binaryData = "\x00\x01\x02\x03\xFF\xFE";
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'payload' => [
                    'data' => base64_encode($binaryData),
                ],
            ])),
        ]);

        $client = new GoogleSecretManagerClient($mockClient);
        $secret = $client->getSecretValue('binary-secret');

        $this->assertEquals($binaryData, $secret['value']);
    }
}
