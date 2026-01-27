<?php

namespace YorCreative\Scrubber\Tests\Unit\SecretManager\Providers;

use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Clients\GoogleSecretManagerClient;
use YorCreative\Scrubber\SecretManager\Providers\GoogleSecretManager;
use YorCreative\Scrubber\SecretManager\Secret;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('GoogleSecretManager')]
#[Group('Unit')]
class GoogleSecretManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scrubber.secret_manager.enabled', true);
        config()->set('scrubber.secret_manager.providers.google.enabled', true);
        config()->set('scrubber.secret_manager.providers.google.project_id', 'test-project');
        config()->set('scrubber.secret_manager.providers.google.keys', ['*']);
    }

    public function test_it_can_load_all_secrets_with_wildcard(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'api-key', 'fullName' => 'projects/test-project/secrets/api-key'],
                ['name' => 'db-password', 'fullName' => 'projects/test-project/secrets/db-password'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('api-key')
            ->once()
            ->andReturn(['name' => 'api-key', 'value' => 'sk_live_abc123']);

        $mockClient->shouldReceive('getSecretValue')
            ->with('db-password')
            ->once()
            ->andReturn(['name' => 'db-password', 'value' => 'super_secret_password']);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertInstanceOf(Collection::class, $secrets);
        $this->assertCount(2, $secrets);
        $this->assertInstanceOf(Secret::class, $secrets->first());
    }

    public function test_it_can_load_specific_secrets(): void
    {
        config()->set('scrubber.secret_manager.providers.google.keys', ['api-key']);

        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('getSecretValue')
            ->with('api-key')
            ->once()
            ->andReturn(['name' => 'api-key', 'value' => 'sk_live_abc123']);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertCount(1, $secrets);
    }

    public function test_it_flattens_json_secrets(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'db-creds', 'fullName' => 'projects/test-project/secrets/db-creds'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('db-creds')
            ->once()
            ->andReturn([
                'name' => 'db-creds',
                'value' => '{"username":"admin","password":"secret123"}',
            ]);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertCount(2, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('db-creds.username', $keys);
        $this->assertContains('db-creds.password', $keys);
    }

    public function test_it_skips_empty_values(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'empty-secret', 'fullName' => 'projects/test-project/secrets/empty-secret'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('empty-secret')
            ->once()
            ->andReturn([
                'name' => 'empty-secret',
                'value' => '',
            ]);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertCount(0, $secrets);
    }

    public function test_it_can_get_specific_secret(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('getSecretValue')
            ->with('my-secret')
            ->once()
            ->andReturn([
                'name' => 'my-secret',
                'value' => 'secret_value_123',
            ]);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secret = GoogleSecretManager::getSpecificSecret('my-secret');

        $this->assertInstanceOf(Secret::class, $secret);
        $this->assertEquals('my-secret', $secret->getKey());
        $this->assertEquals('secret_value_123', Secret::decrypt($secret->getVariable()));
    }

    public function test_it_handles_numeric_values_in_json(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'db-config', 'fullName' => 'projects/test-project/secrets/db-config'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('db-config')
            ->once()
            ->andReturn([
                'name' => 'db-config',
                'value' => '{"host":"localhost","port":5432,"timeout":30.5}',
            ]);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertCount(3, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('db-config.host', $keys);
        $this->assertContains('db-config.port', $keys);
        $this->assertContains('db-config.timeout', $keys);
    }

    public function test_it_skips_boolean_and_null_in_json(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'feature-flags', 'fullName' => 'projects/test-project/secrets/feature-flags'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('feature-flags')
            ->once()
            ->andReturn([
                'name' => 'feature-flags',
                'value' => '{"enabled":true,"disabled":false,"empty":null,"api_key":"keep_this"}',
            ]);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertCount(1, $secrets);
        $this->assertEquals('feature-flags.api_key', $secrets->first()->getKey());
    }

    public function test_it_continues_on_individual_secret_failures(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'good-secret', 'fullName' => 'projects/test-project/secrets/good-secret'],
                ['name' => 'bad-secret', 'fullName' => 'projects/test-project/secrets/bad-secret'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('good-secret')
            ->once()
            ->andReturn(['name' => 'good-secret', 'value' => 'good_value']);

        $mockClient->shouldReceive('getSecretValue')
            ->with('bad-secret')
            ->once()
            ->andThrow(new \Exception('Access denied'));

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        // Should have 1 secret (the good one), not crash
        $this->assertCount(1, $secrets);
    }

    public function test_it_handles_nested_json_secrets(): void
    {
        $mockClient = Mockery::mock(GoogleSecretManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['name' => 'nested-config', 'fullName' => 'projects/test-project/secrets/nested-config'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('nested-config')
            ->once()
            ->andReturn([
                'name' => 'nested-config',
                'value' => '{"database":{"host":"localhost","credentials":{"username":"admin","password":"secret123"}}}',
            ]);

        $this->instance(GoogleSecretManagerClient::class, $mockClient);

        $secrets = GoogleSecretManager::getAllSecrets();

        $this->assertCount(3, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('nested-config.database.host', $keys);
        $this->assertContains('nested-config.database.credentials.username', $keys);
        $this->assertContains('nested-config.database.credentials.password', $keys);
    }
}
