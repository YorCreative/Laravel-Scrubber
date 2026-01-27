<?php

namespace YorCreative\Scrubber\SecretManager\Providers;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use YorCreative\Scrubber\Clients\GoogleSecretManagerClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\SecretManager\Secret;

class GoogleSecretManager implements SecretProviderInterface
{
    protected const MAX_RECURSION_DEPTH = 10;

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
        $keys = Config::get('scrubber.secret_manager.providers.google.keys', ['*']);
        $secretCollection = new Collection;

        if (in_array('*', $keys)) {
            $secrets = $client->listSecrets();

            foreach ($secrets as $secretMetadata) {
                try {
                    $secretData = $client->getSecretValue($secretMetadata['name']);
                    self::addSecretsFromValue($secretCollection, $secretData['name'], $secretData['value']);
                } catch (Exception $e) {
                    // Skip secrets that fail to retrieve (may be disabled or access denied)
                }
            }
        } else {
            foreach ($keys as $key) {
                try {
                    $secretData = $client->getSecretValue($key);
                    self::addSecretsFromValue($secretCollection, $secretData['name'], $secretData['value']);
                } catch (Exception $e) {
                    // Skip secrets that fail to retrieve
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
    protected static function getClient(): GoogleSecretManagerClient
    {
        try {
            return app(GoogleSecretManagerClient::class);
        } catch (Exception $exception) {
            throw new SecretProviderException($exception->getMessage(), 0, $exception);
        }
    }

    protected static function addSecretsFromValue(Collection $collection, string $name, string $value): void
    {
        if (empty($value)) {
            return;
        }

        // Try to decode as JSON (GCP supports JSON secret values)
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            self::addSecretsFromData($collection, $name, $decoded, 0);
        } else {
            $collection->push(new Secret($name, $value));
        }
    }

    protected static function addSecretsFromData(Collection $collection, string $path, array $data, int $depth): void
    {
        if ($depth >= self::MAX_RECURSION_DEPTH) {
            return;
        }

        foreach ($data as $key => $value) {
            $secretPath = $path.'.'.$key;

            if (is_string($value) && $value !== '') {
                $collection->push(new Secret($secretPath, $value));
            } elseif (is_numeric($value)) {
                $collection->push(new Secret($secretPath, (string) $value));
            } elseif (is_array($value)) {
                self::addSecretsFromData($collection, $secretPath, $value, $depth + 1);
            }
            // Skip booleans and null values
        }
    }
}
