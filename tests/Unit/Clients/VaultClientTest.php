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
use YorCreative\Scrubber\Clients\VaultClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('VaultClient')]
#[Group('Unit')]
class VaultClientTest extends TestCase
{
    protected array $requestHistory = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scrubber.secret_manager.providers.vault.host', 'http://127.0.0.1:8200');
        config()->set('scrubber.secret_manager.providers.vault.token', 'test-token');
        config()->set('scrubber.secret_manager.providers.vault.engine', 'secret');
        config()->set('scrubber.secret_manager.providers.vault.namespace', null);
        config()->set('scrubber.secret_manager.providers.vault.version', 2);
        config()->set('scrubber.secret_manager.providers.vault.path', '');

        $this->requestHistory = [];
    }

    protected function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        $history = Middleware::history($this->requestHistory);
        $handlerStack->push($history);

        return new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://127.0.0.1:8200',
        ]);
    }

    // ==================== listSecrets() Tests ====================

    public function test_list_secrets_returns_array_of_keys(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [
                    'keys' => ['secret1', 'secret2', 'secret3'],
                ],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(3, $secrets);
        $this->assertEquals(['secret1', 'secret2', 'secret3'], $secrets);
    }

    public function test_list_secrets_with_path(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [
                    'keys' => ['nested-secret'],
                ],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $client->listSecrets('myapp/config');

        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/v1/secret/metadata/myapp/config', $request->getUri()->getPath());
    }

    public function test_list_secrets_handles_empty_response(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [
                    'keys' => [],
                ],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(0, $secrets);
        $this->assertIsArray($secrets);
    }

    public function test_list_secrets_handles_missing_keys(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(0, $secrets);
    }

    public function test_list_secrets_throws_on_http_error(): void
    {
        $mockClient = $this->createMockClient([
            new Response(403, [], json_encode([
                'errors' => ['permission denied'],
            ])),
        ]);

        $client = new VaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to list Vault secrets');

        $client->listSecrets();
    }

    public function test_list_secrets_throws_on_network_error(): void
    {
        $mockClient = $this->createMockClient([
            new RequestException(
                'Connection timed out',
                new Request('LIST', '/v1/secret/metadata/')
            ),
        ]);

        $client = new VaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to list Vault secrets');

        $client->listSecrets();
    }

    public function test_list_secrets_throws_on_invalid_json(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], 'not valid json'),
        ]);

        $client = new VaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Invalid JSON response from Vault');

        $client->listSecrets();
    }

    // ==================== getSecret() Tests ====================

    public function test_get_secret_returns_data(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [
                    'data' => [
                        'username' => 'admin',
                        'password' => 'secret123',
                    ],
                ],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $secret = $client->getSecret('myapp/credentials');

        $this->assertEquals('admin', $secret['username']);
        $this->assertEquals('secret123', $secret['password']);
    }

    public function test_get_secret_uses_kv_v2_data_path(): void
    {
        config()->set('scrubber.secret_manager.providers.vault.version', 2);

        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [
                    'data' => ['key' => 'value'],
                ],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $client->getSecret('myapp/config');

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/v1/secret/data/myapp/config', $request->getUri()->getPath());
    }

    public function test_get_secret_uses_kv_v1_path(): void
    {
        config()->set('scrubber.secret_manager.providers.vault.version', 1);

        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => ['key' => 'value'],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $secret = $client->getSecret('myapp/config');

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/v1/secret/myapp/config', $request->getUri()->getPath());
        $this->assertStringNotContainsString('/data/', $request->getUri()->getPath());

        $this->assertEquals('value', $secret['key']);
    }

    public function test_get_secret_handles_empty_data(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'data' => [
                    'data' => [],
                ],
            ])),
        ]);

        $client = new VaultClient($mockClient);
        $secret = $client->getSecret('empty-secret');

        $this->assertIsArray($secret);
        $this->assertEmpty($secret);
    }

    public function test_get_secret_throws_on_not_found(): void
    {
        $mockClient = $this->createMockClient([
            new Response(404, [], json_encode([
                'errors' => [],
            ])),
        ]);

        $client = new VaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to get Vault secret');

        $client->getSecret('nonexistent');
    }

    public function test_get_secret_throws_on_forbidden(): void
    {
        $mockClient = $this->createMockClient([
            new Response(403, [], json_encode([
                'errors' => ['permission denied'],
            ])),
        ]);

        $client = new VaultClient($mockClient);

        $this->expectException(SecretProviderException::class);

        $client->getSecret('forbidden-secret');
    }

    public function test_get_secret_throws_on_invalid_json(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], 'invalid json response'),
        ]);

        $client = new VaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Invalid JSON response from Vault');

        $client->getSecret('some-secret');
    }

    // ==================== Configuration Tests ====================

    public function test_uses_custom_engine(): void
    {
        config()->set('scrubber.secret_manager.providers.vault.engine', 'kv');

        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['data' => ['keys' => []]])),
        ]);

        $client = new VaultClient($mockClient);
        $client->listSecrets();

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/v1/kv/metadata/', $request->getUri()->getPath());
    }

    public function test_uses_base_path(): void
    {
        config()->set('scrubber.secret_manager.providers.vault.path', 'myapp/production');

        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['data' => ['keys' => []]])),
        ]);

        $client = new VaultClient($mockClient);
        $client->listSecrets('config');

        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/v1/secret/metadata/myapp/production/config', $request->getUri()->getPath());
    }
}
