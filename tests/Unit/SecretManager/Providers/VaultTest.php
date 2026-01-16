<?php

namespace YorCreative\Scrubber\Tests\Unit\SecretManager\Providers;

use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Clients\VaultClient;
use YorCreative\Scrubber\SecretManager\Providers\Vault;
use YorCreative\Scrubber\SecretManager\Secret;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('Vault')]
#[Group('Unit')]
class VaultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scrubber.secret_manager.enabled', true);
        config()->set('scrubber.secret_manager.providers.vault.enabled', true);
        config()->set('scrubber.secret_manager.providers.vault.keys', ['*']);
        config()->set('scrubber.secret_manager.providers.vault.host', 'http://127.0.0.1:8200');
        config()->set('scrubber.secret_manager.providers.vault.token', 'test-token');
        config()->set('scrubber.secret_manager.providers.vault.engine', 'secret');
        config()->set('scrubber.secret_manager.providers.vault.version', 2);
    }

    public function test_it_can_load_secrets_from_vault()
    {
        $mockClient = Mockery::mock(VaultClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->with('')
            ->once()
            ->andReturn(['api-key', 'db-password']);

        $mockClient->shouldReceive('getSecret')
            ->with('api-key')
            ->once()
            ->andReturn(['value' => 'sk_live_abc123']);

        $mockClient->shouldReceive('getSecret')
            ->with('db-password')
            ->once()
            ->andReturn(['password' => 'super_secret']);

        $this->instance(VaultClient::class, $mockClient);

        $secrets = Vault::getAllSecrets();

        $this->assertInstanceOf(Collection::class, $secrets);
        $this->assertCount(2, $secrets);
        $this->assertInstanceOf(Secret::class, $secrets->first());
    }

    public function test_it_handles_nested_directories()
    {
        $mockClient = Mockery::mock(VaultClient::class);

        // First level - includes a directory
        $mockClient->shouldReceive('listSecrets')
            ->with('')
            ->once()
            ->andReturn(['api-key', 'nested/']);

        // The api-key secret
        $mockClient->shouldReceive('getSecret')
            ->with('api-key')
            ->once()
            ->andReturn(['value' => 'api_value']);

        // List the nested directory
        $mockClient->shouldReceive('listSecrets')
            ->with('nested')
            ->once()
            ->andReturn(['inner-secret']);

        // The nested secret
        $mockClient->shouldReceive('getSecret')
            ->with('nested/inner-secret')
            ->once()
            ->andReturn(['value' => 'inner_value']);

        $this->instance(VaultClient::class, $mockClient);

        $secrets = Vault::getAllSecrets();

        $this->assertCount(2, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('api-key.value', $keys);
        $this->assertContains('nested/inner-secret.value', $keys);
    }

    public function test_it_handles_numeric_values()
    {
        $mockClient = Mockery::mock(VaultClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->with('')
            ->once()
            ->andReturn(['config']);

        $mockClient->shouldReceive('getSecret')
            ->with('config')
            ->once()
            ->andReturn([
                'port' => 5432,
                'timeout' => 30.5,
            ]);

        $this->instance(VaultClient::class, $mockClient);

        $secrets = Vault::getAllSecrets();

        $this->assertCount(2, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('config.port', $keys);
        $this->assertContains('config.timeout', $keys);
    }

    public function test_it_skips_boolean_and_null_values()
    {
        $mockClient = Mockery::mock(VaultClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->with('')
            ->once()
            ->andReturn(['config']);

        $mockClient->shouldReceive('getSecret')
            ->with('config')
            ->once()
            ->andReturn([
                'enabled' => true,
                'disabled' => false,
                'empty' => null,
                'valid' => 'keep_this',
            ]);

        $this->instance(VaultClient::class, $mockClient);

        $secrets = Vault::getAllSecrets();

        $this->assertCount(1, $secrets);
        $this->assertEquals('config.valid', $secrets->first()->getKey());
    }

    public function test_it_can_load_specific_secrets()
    {
        config()->set('scrubber.secret_manager.providers.vault.keys', ['specific-key']);

        $mockClient = Mockery::mock(VaultClient::class);
        $mockClient->shouldReceive('getSecret')
            ->with('specific-key')
            ->once()
            ->andReturn(['value' => 'specific_value']);

        $this->instance(VaultClient::class, $mockClient);

        $secrets = Vault::getAllSecrets();

        $this->assertCount(1, $secrets);
    }

    public function test_it_can_get_specific_secret()
    {
        $mockClient = Mockery::mock(VaultClient::class);
        $mockClient->shouldReceive('getSecret')
            ->with('my-secret')
            ->once()
            ->andReturn(['password' => 'secret_password_123']);

        $this->instance(VaultClient::class, $mockClient);

        $secret = Vault::getSpecificSecret('my-secret');

        $this->assertInstanceOf(Secret::class, $secret);
        $this->assertEquals('my-secret.password', $secret->getKey());
        $this->assertEquals('secret_password_123', Secret::decrypt($secret->getVariable()));
    }

    public function test_it_continues_on_individual_secret_failures()
    {
        $mockClient = Mockery::mock(VaultClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->with('')
            ->once()
            ->andReturn(['good-secret', 'bad-secret']);

        $mockClient->shouldReceive('getSecret')
            ->with('good-secret')
            ->once()
            ->andReturn(['value' => 'good_value']);

        $mockClient->shouldReceive('getSecret')
            ->with('bad-secret')
            ->once()
            ->andThrow(new \Exception('Access denied'));

        $this->instance(VaultClient::class, $mockClient);

        $secrets = Vault::getAllSecrets();

        // Should have 1 secret (the good one), not crash
        $this->assertCount(1, $secrets);
    }

    public function test_it_respects_max_recursion_depth()
    {
        $mockClient = Mockery::mock(VaultClient::class);

        // Create a deeply nested structure (11 levels to exceed MAX_RECURSION_DEPTH of 10)
        for ($i = 0; $i <= 10; $i++) {
            $path = $i === 0 ? '' : str_repeat('level/', $i);
            $path = rtrim($path, '/');
            $mockClient->shouldReceive('listSecrets')
                ->with($path)
                ->andReturn(['level/']);
        }

        $this->instance(VaultClient::class, $mockClient);

        // Should not throw or hang, should just stop at depth 10
        $secrets = Vault::getAllSecrets();

        $this->assertInstanceOf(Collection::class, $secrets);
    }
}
