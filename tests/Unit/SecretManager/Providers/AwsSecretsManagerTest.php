<?php

namespace YorCreative\Scrubber\Tests\Unit\SecretManager\Providers;

use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Clients\AwsSecretsManagerClient;
use YorCreative\Scrubber\SecretManager\Providers\AwsSecretsManager;
use YorCreative\Scrubber\SecretManager\Secret;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('AwsSecretsManager')]
#[Group('Unit')]
class AwsSecretsManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scrubber.secret_manager.enabled', true);
        config()->set('scrubber.secret_manager.providers.aws.enabled', true);
        config()->set('scrubber.secret_manager.providers.aws.keys', ['*']);
    }

    public function test_it_can_load_all_secrets_with_wildcard()
    {
        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['Name' => 'prod/api-key', 'ARN' => 'arn:aws:secretsmanager:us-east-1:123:secret:prod/api-key'],
                ['Name' => 'prod/db-password', 'ARN' => 'arn:aws:secretsmanager:us-east-1:123:secret:prod/db-password'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('prod/api-key')
            ->once()
            ->andReturn(['name' => 'prod/api-key', 'value' => 'sk_live_abc123']);

        $mockClient->shouldReceive('getSecretValue')
            ->with('prod/db-password')
            ->once()
            ->andReturn(['name' => 'prod/db-password', 'value' => 'super_secret_password']);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secrets = AwsSecretsManager::getAllSecrets();

        $this->assertInstanceOf(Collection::class, $secrets);
        $this->assertCount(2, $secrets);
        $this->assertInstanceOf(Secret::class, $secrets->first());
    }

    public function test_it_can_load_specific_secrets()
    {
        config()->set('scrubber.secret_manager.providers.aws.keys', ['prod/api-key']);

        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('getSecretValue')
            ->with('prod/api-key')
            ->once()
            ->andReturn(['name' => 'prod/api-key', 'value' => 'sk_live_abc123']);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secrets = AwsSecretsManager::getAllSecrets();

        $this->assertCount(1, $secrets);
    }

    public function test_it_flattens_json_secrets()
    {
        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['Name' => 'prod/db-creds'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('prod/db-creds')
            ->once()
            ->andReturn([
                'name' => 'prod/db-creds',
                'value' => '{"username":"admin","password":"secret123"}',
            ]);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secrets = AwsSecretsManager::getAllSecrets();

        $this->assertCount(2, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('prod/db-creds.username', $keys);
        $this->assertContains('prod/db-creds.password', $keys);
    }

    public function test_it_skips_empty_values()
    {
        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['Name' => 'empty-secret'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('empty-secret')
            ->once()
            ->andReturn([
                'name' => 'empty-secret',
                'value' => '',
            ]);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secrets = AwsSecretsManager::getAllSecrets();

        $this->assertCount(0, $secrets);
    }

    public function test_it_can_get_specific_secret()
    {
        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('getSecretValue')
            ->with('my-secret')
            ->once()
            ->andReturn([
                'name' => 'my-secret',
                'value' => 'secret_value_123',
            ]);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secret = AwsSecretsManager::getSpecificSecret('my-secret');

        $this->assertInstanceOf(Secret::class, $secret);
        $this->assertEquals('my-secret', $secret->getKey());
        $this->assertEquals('secret_value_123', Secret::decrypt($secret->getVariable()));
    }

    public function test_it_handles_numeric_values_in_json()
    {
        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['Name' => 'db-config'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('db-config')
            ->once()
            ->andReturn([
                'name' => 'db-config',
                'value' => '{"host":"localhost","port":5432,"timeout":30.5}',
            ]);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secrets = AwsSecretsManager::getAllSecrets();

        $this->assertCount(3, $secrets);

        $keys = $secrets->map(fn ($s) => $s->getKey())->toArray();
        $this->assertContains('db-config.host', $keys);
        $this->assertContains('db-config.port', $keys);
        $this->assertContains('db-config.timeout', $keys);
    }

    public function test_it_skips_boolean_and_null_in_json()
    {
        $mockClient = Mockery::mock(AwsSecretsManagerClient::class);
        $mockClient->shouldReceive('listSecrets')
            ->once()
            ->andReturn([
                ['Name' => 'feature-flags'],
            ]);

        $mockClient->shouldReceive('getSecretValue')
            ->with('feature-flags')
            ->once()
            ->andReturn([
                'name' => 'feature-flags',
                'value' => '{"enabled":true,"disabled":false,"empty":null,"api_key":"keep_this"}',
            ]);

        $this->instance(AwsSecretsManagerClient::class, $mockClient);

        $secrets = AwsSecretsManager::getAllSecrets();

        $this->assertCount(1, $secrets);
        $this->assertEquals('feature-flags.api_key', $secrets->first()->getKey());
    }
}
