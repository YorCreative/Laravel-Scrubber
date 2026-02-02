<?php

namespace YorCreative\Scrubber\SecretManager\Providers;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use YorCreative\Scrubber\Clients\AwsSecretsManagerClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\SecretManager\Secret;

class AwsSecretsManager implements SecretProviderInterface
{
    /**
     * @throws SecretProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getSpecificSecret(string $key): Secret
    {
        $secretData = self::getClient()->getSecretValue($key);

        return new Secret($secretData['name'], $secretData['value']);
    }

    /**
     * @throws SecretProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getAllSecrets(): Collection
    {
        $client = self::getClient();
        $keys = Config::get('scrubber.secret_manager.providers.aws.keys', ['*']);
        $secretCollection = new Collection;

        $continueOnFailure = Config::get('scrubber.secret_manager.continue_on_failure', true);

        if (in_array('*', $keys)) {
            $secrets = $client->listSecrets();

            foreach ($secrets as $secretMetadata) {
                try {
                    $secretData = $client->getSecretValue($secretMetadata['Name'] ?? $secretMetadata['ARN']);
                    self::addSecretsFromValue($secretCollection, $secretData['name'], $secretData['value']);
                } catch (Exception $e) {
                    if (! $continueOnFailure) {
                        throw new SecretProviderException('Failed to retrieve AWS secret: '.$e->getMessage(), 0, $e);
                    }
                }
            }
        } else {
            foreach ($keys as $key) {
                try {
                    $secretData = $client->getSecretValue($key);
                    self::addSecretsFromValue($secretCollection, $secretData['name'], $secretData['value']);
                } catch (Exception $e) {
                    if (! $continueOnFailure) {
                        throw new SecretProviderException('Failed to retrieve AWS secret: '.$e->getMessage(), 0, $e);
                    }
                }
            }
        }

        return $secretCollection;
    }

    /**
     * @throws SecretProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function getClient(): AwsSecretsManagerClient
    {
        try {
            return app(AwsSecretsManagerClient::class);
        } catch (Exception $exception) {
            throw new SecretProviderException($exception->getMessage(), 0, $exception);
        }
    }

    protected static function addSecretsFromValue(Collection $collection, string $name, string $value): void
    {
        if (empty($value)) {
            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as $key => $val) {
                if (is_string($val) && $val !== '') {
                    $collection->push(new Secret($name.'.'.$key, $val));
                } elseif (is_numeric($val)) {
                    // Include numeric values as strings (consistent with Vault provider)
                    $collection->push(new Secret($name.'.'.$key, (string) $val));
                }
                // Skip booleans, null, arrays, and objects
            }
        } else {
            $collection->push(new Secret($name, $value));
        }
    }
}
