<?php

namespace YorCreative\Scrubber\Clients;

use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Exceptions\MissingDependencyException;
use YorCreative\Scrubber\Exceptions\SecretProviderException;

class AwsSecretsManagerClient
{
    protected object $client;

    public function __construct()
    {
        if (! class_exists(\Aws\SecretsManager\SecretsManagerClient::class)) {
            throw MissingDependencyException::forPackage('aws/aws-sdk-php', 'AWS Secrets Manager');
        }

        $config = [
            'version' => Config::get('scrubber.secret_manager.providers.aws.version', 'latest'),
            'region' => Config::get('scrubber.secret_manager.providers.aws.region', 'us-east-1'),
        ];

        $credentials = Config::get('scrubber.secret_manager.providers.aws.credentials');
        if (! empty($credentials['key']) && ! empty($credentials['secret'])) {
            $config['credentials'] = [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ];
        }

        $this->client = new \Aws\SecretsManager\SecretsManagerClient($config);
    }

    /**
     * @throws SecretProviderException
     */
    public function listSecrets(): array
    {
        try {
            $secrets = [];
            $nextToken = null;

            do {
                $params = [];
                if ($nextToken) {
                    $params['NextToken'] = $nextToken;
                }

                $result = $this->client->listSecrets($params);
                $secrets = array_merge($secrets, $result->get('SecretList') ?? []);
                $nextToken = $result->get('NextToken');
            } while ($nextToken);

            return $secrets;
        } catch (\Aws\Exception\AwsException $e) {
            throw new SecretProviderException('Failed to list AWS secrets: '.$e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new SecretProviderException('Failed to list AWS secrets: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws SecretProviderException
     */
    public function getSecretValue(string $secretId): array
    {
        try {
            $result = $this->client->getSecretValue([
                'SecretId' => $secretId,
            ]);

            $secretValue = $result->get('SecretString');
            if ($secretValue === null) {
                $binaryData = $result->get('SecretBinary');
                if ($binaryData !== null) {
                    $decoded = base64_decode($binaryData);
                    $secretValue = $decoded !== false ? $decoded : '';
                } else {
                    $secretValue = '';
                }
            }

            return [
                'name' => $result->get('Name'),
                'value' => $secretValue,
            ];
        } catch (\Aws\Exception\AwsException $e) {
            throw new SecretProviderException('Failed to get AWS secret: '.$e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new SecretProviderException('Failed to get AWS secret: '.$e->getMessage(), 0, $e);
        }
    }
}
