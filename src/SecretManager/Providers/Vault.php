<?php

namespace YorCreative\Scrubber\SecretManager\Providers;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use YorCreative\Scrubber\Clients\VaultClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\SecretManager\Secret;

class Vault implements SecretProviderInterface
{
    protected const MAX_RECURSION_DEPTH = 10;

    /**
     * @throws SecretProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getSpecificSecret(string $key): Secret
    {
        $secretData = self::getClient()->getSecret($key);

        // Find the first string value in the secret data
        foreach ($secretData as $dataKey => $value) {
            if (is_string($value) && ! empty($value)) {
                return new Secret($key.'.'.$dataKey, $value);
            }
        }

        // Fallback: JSON encode the entire data if no simple string found
        $value = json_encode($secretData);
        if ($value === false) {
            throw new SecretProviderException('Failed to encode Vault secret data for key: '.$key);
        }

        return new Secret($key, $value);
    }

    /**
     * @throws SecretProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getAllSecrets(): Collection
    {
        $client = self::getClient();
        $keys = Config::get('scrubber.secret_manager.providers.vault.keys', ['*']);
        $secretCollection = new Collection;

        $continueOnFailure = Config::get('scrubber.secret_manager.continue_on_failure', true);

        if (in_array('*', $keys)) {
            self::fetchSecretsRecursively($client, '', $secretCollection, 0, $continueOnFailure);
        } else {
            foreach ($keys as $key) {
                try {
                    $secretData = $client->getSecret($key);
                    self::addSecretsFromData($secretCollection, $key, $secretData);
                } catch (Exception $e) {
                    if (! $continueOnFailure) {
                        throw new SecretProviderException('Failed to retrieve Vault secret: '.$e->getMessage(), 0, $e);
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
    protected static function getClient(): VaultClient
    {
        try {
            return app(VaultClient::class);
        } catch (Exception $exception) {
            throw new SecretProviderException($exception->getMessage(), 0, $exception);
        }
    }

    protected static function fetchSecretsRecursively(VaultClient $client, string $path, Collection $collection, int $depth, bool $continueOnFailure = true): void
    {
        if ($depth >= self::MAX_RECURSION_DEPTH) {
            return;
        }

        try {
            $keys = $client->listSecrets($path);

            foreach ($keys as $key) {
                $cleanKey = rtrim($key, '/');
                $fullPath = $path === '' ? $cleanKey : $path.'/'.$cleanKey;

                if (str_ends_with($key, '/')) {
                    self::fetchSecretsRecursively($client, $fullPath, $collection, $depth + 1, $continueOnFailure);
                } else {
                    try {
                        $secretData = $client->getSecret($fullPath);
                        self::addSecretsFromData($collection, $fullPath, $secretData);
                    } catch (Exception $e) {
                        if (! $continueOnFailure) {
                            throw new SecretProviderException('Failed to retrieve Vault secret: '.$e->getMessage(), 0, $e);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // If listing fails, the path might be a direct secret
            if ($path !== '') {
                try {
                    $secretData = $client->getSecret($path);
                    self::addSecretsFromData($collection, $path, $secretData);
                } catch (Exception $innerException) {
                    if (! $continueOnFailure) {
                        throw new SecretProviderException('Failed to retrieve Vault secret: '.$innerException->getMessage(), 0, $innerException);
                    }
                }
            } elseif (! $continueOnFailure) {
                throw new SecretProviderException('Failed to list Vault secrets: '.$e->getMessage(), 0, $e);
            }
        }
    }

    protected static function addSecretsFromData(Collection $collection, string $path, array $data): void
    {
        foreach ($data as $key => $value) {
            $secretPath = $path.'.'.$key;

            if (is_string($value) && $value !== '') {
                $collection->push(new Secret($secretPath, $value));
            } elseif (is_numeric($value)) {
                // Include numeric values as strings
                $collection->push(new Secret($secretPath, (string) $value));
            } elseif (is_array($value)) {
                self::addSecretsFromData($collection, $secretPath, $value);
            }
            // Skip booleans and null values
        }
    }
}
