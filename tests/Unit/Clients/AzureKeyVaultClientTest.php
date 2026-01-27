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
use YorCreative\Scrubber\Clients\AzureKeyVaultClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('AzureKeyVaultClient')]
#[Group('Unit')]
class AzureKeyVaultClientTest extends TestCase
{
    protected array $requestHistory = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scrubber.secret_manager.providers.azure.vault_url', 'https://test-vault.vault.azure.net');
        config()->set('scrubber.secret_manager.providers.azure.access_token', 'test-access-token');
        config()->set('scrubber.secret_manager.providers.azure.tenant_id', null);
        config()->set('scrubber.secret_manager.providers.azure.client_id', null);
        config()->set('scrubber.secret_manager.providers.azure.client_secret', null);

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
            'base_uri' => 'https://test-vault.vault.azure.net',
        ]);
    }

    // ==================== listSecrets() Tests ====================

    public function test_list_secrets_returns_array_of_secrets(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'value' => [
                    ['id' => 'https://test-vault.vault.azure.net/secrets/secret1'],
                    ['id' => 'https://test-vault.vault.azure.net/secrets/secret2'],
                ],
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(2, $secrets);
        $this->assertEquals('secret1', $secrets[0]['name']);
        $this->assertEquals('secret2', $secrets[1]['name']);
    }

    public function test_list_secrets_includes_correct_api_version(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['value' => []])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $client->listSecrets();

        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('api-version=7.4', $request->getUri()->getQuery());
    }

    public function test_list_secrets_handles_pagination(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'value' => [
                    ['id' => 'https://test-vault.vault.azure.net/secrets/secret1'],
                ],
                'nextLink' => 'https://test-vault.vault.azure.net/secrets?api-version=7.4&$skiptoken=page2',
            ])),
            new Response(200, [], json_encode([
                'value' => [
                    ['id' => 'https://test-vault.vault.azure.net/secrets/secret2'],
                ],
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(2, $secrets);
        $this->assertEquals('secret1', $secrets[0]['name']);
        $this->assertEquals('secret2', $secrets[1]['name']);

        // Verify two requests were made
        $this->assertCount(2, $this->requestHistory);
    }

    public function test_list_secrets_handles_empty_response(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['value' => []])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(0, $secrets);
        $this->assertIsArray($secrets);
    }

    public function test_list_secrets_handles_missing_value_key(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([])), // No 'value' key
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secrets = $client->listSecrets();

        $this->assertCount(0, $secrets);
    }

    public function test_list_secrets_throws_on_http_error(): void
    {
        $mockClient = $this->createMockClient([
            new Response(403, [], json_encode([
                'error' => ['code' => 'Forbidden', 'message' => 'Access denied'],
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to list Azure Key Vault secrets');

        $client->listSecrets();
    }

    public function test_list_secrets_throws_on_network_error(): void
    {
        $mockClient = $this->createMockClient([
            new RequestException(
                'Connection timed out',
                new Request('GET', '/secrets')
            ),
        ]);

        $client = new AzureKeyVaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to list Azure Key Vault secrets');

        $client->listSecrets();
    }

    // ==================== getSecretValue() Tests ====================

    public function test_get_secret_value_returns_secret_data(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'id' => 'https://test-vault.vault.azure.net/secrets/my-secret/version123',
                'value' => 'super-secret-value',
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secret = $client->getSecretValue('my-secret');

        $this->assertEquals('my-secret', $secret['name']);
        $this->assertEquals('super-secret-value', $secret['value']);
    }

    public function test_get_secret_value_extracts_name_from_id(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'id' => 'https://test-vault.vault.azure.net/secrets/extracted-name/abc123',
                'value' => 'test',
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secret = $client->getSecretValue('any-name');

        // Should extract 'extracted-name' from the URL path (not the version 'abc123')
        $this->assertEquals('extracted-name', $secret['name']);
        $this->assertEquals('test', $secret['value']);
    }

    public function test_get_secret_value_with_specific_version(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'id' => 'https://test-vault.vault.azure.net/secrets/my-secret/specific-version',
                'value' => 'versioned-value',
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secret = $client->getSecretValue('my-secret', 'specific-version');

        $this->assertEquals('versioned-value', $secret['value']);

        // Verify the URL includes the version
        $request = $this->requestHistory[0]['request'];
        $this->assertStringContainsString('/secrets/my-secret/specific-version', $request->getUri()->getPath());
    }

    public function test_get_secret_value_handles_missing_value(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'id' => 'https://test-vault.vault.azure.net/secrets/empty-secret',
                // No 'value' key
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secret = $client->getSecretValue('empty-secret');

        $this->assertEquals('', $secret['value']);
    }

    public function test_get_secret_value_handles_missing_id(): void
    {
        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode([
                'value' => 'some-value',
                // No 'id' key
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);
        $secret = $client->getSecretValue('fallback-name');

        // Should fall back to the provided name
        $this->assertEquals('fallback-name', $secret['name']);
    }

    public function test_get_secret_value_throws_on_not_found(): void
    {
        $mockClient = $this->createMockClient([
            new Response(404, [], json_encode([
                'error' => ['code' => 'SecretNotFound', 'message' => 'Secret not found'],
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Failed to get Azure Key Vault secret');

        $client->getSecretValue('nonexistent');
    }

    public function test_get_secret_value_throws_on_forbidden(): void
    {
        $mockClient = $this->createMockClient([
            new Response(403, [], json_encode([
                'error' => ['code' => 'Forbidden', 'message' => 'Access denied'],
            ])),
        ]);

        $client = new AzureKeyVaultClient($mockClient);

        $this->expectException(SecretProviderException::class);

        $client->getSecretValue('forbidden-secret');
    }

    // ==================== Authentication Tests ====================

    public function test_throws_when_no_auth_configured(): void
    {
        config()->set('scrubber.secret_manager.providers.azure.access_token', null);
        config()->set('scrubber.secret_manager.providers.azure.tenant_id', null);
        config()->set('scrubber.secret_manager.providers.azure.client_id', null);
        config()->set('scrubber.secret_manager.providers.azure.client_secret', null);

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Azure Key Vault authentication not configured');

        new AzureKeyVaultClient;
    }

    public function test_uses_direct_access_token_when_provided(): void
    {
        config()->set('scrubber.secret_manager.providers.azure.access_token', 'my-direct-token');

        $mockClient = $this->createMockClient([
            new Response(200, [], json_encode(['value' => []])),
        ]);

        // Should not throw - direct token is used
        $client = new AzureKeyVaultClient($mockClient);
        $client->listSecrets();

        $this->assertTrue(true); // If we got here, auth succeeded
    }

    public function test_requires_all_client_credentials(): void
    {
        config()->set('scrubber.secret_manager.providers.azure.access_token', null);
        config()->set('scrubber.secret_manager.providers.azure.tenant_id', 'tenant');
        config()->set('scrubber.secret_manager.providers.azure.client_id', 'client');
        config()->set('scrubber.secret_manager.providers.azure.client_secret', null); // Missing!

        $this->expectException(SecretProviderException::class);
        $this->expectExceptionMessage('Azure Key Vault authentication not configured');

        new AzureKeyVaultClient;
    }
}
