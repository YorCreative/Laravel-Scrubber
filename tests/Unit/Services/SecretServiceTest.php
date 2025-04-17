<?php

namespace YorCreative\Scrubber\Tests\Unit\Services;

use YorCreative\Scrubber\SecretManager\Providers\Gitlab;
use YorCreative\Scrubber\SecretManager\Secret;
use YorCreative\Scrubber\SecretManager\SecretManager;
use YorCreative\Scrubber\Tests\TestCase;

class SecretServiceTest extends TestCase
{
    public function test_it_can_load_secrets_from_gitlab()
    {
        $secrets = SecretManager::getSecrets(Gitlab::class);

        $this->assertCount(2, $secrets);
        $this->assertInstanceOf(Secret::class, $secrets->first());

        $this->assertEquals(
            Secret::decrypt($secrets->first()->getVariable()),
            json_decode($this->mockSecretsResponse, true)[0]['value']
        );
    }
}
