<?php

namespace YorCreative\Scrubber\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Exceptions\SecretProviderException;

/**
 * Azure Key Vault client using REST API.
 *
 * Supports three authentication methods:
 * - Client credentials (tenant_id, client_id, client_secret)
 * - Managed Identity (auto-detected in Azure environments)
 * - Direct access token
 */
class AzureKeyVaultClient
{
    protected Client $httpClient;

    protected string $vaultUrl;

    protected string $accessToken;

    protected const API_VERSION = '7.4';

    public function __construct(?Client $httpClient = null)
    {
        $this->vaultUrl = rtrim(Config::get('scrubber.secret_manager.providers.azure.vault_url', ''), '/');
        $this->accessToken = $this->resolveAccessToken();

        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => $this->vaultUrl,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * List all secrets in the vault.
     *
     * @throws SecretProviderException
     */
    public function listSecrets(): array
    {
        try {
            $secrets = [];
            $url = '/secrets?api-version='.self::API_VERSION;

            do {
                $response = $this->httpClient->get($url);
                $data = json_decode($response->getBody()->getContents(), true);

                foreach ($data['value'] ?? [] as $secret) {
                    $secrets[] = [
                        'name' => basename($secret['id']),
                        'id' => $secret['id'],
                    ];
                }

                $url = $data['nextLink'] ?? null;
                if ($url) {
                    // nextLink is a full URL, extract path and query
                    $parsed = parse_url($url);
                    $url = ($parsed['path'] ?? '').(isset($parsed['query']) ? '?'.$parsed['query'] : '');
                }
            } while ($url);

            return $secrets;
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to list Azure Key Vault secrets: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a secret value by name.
     *
     * @throws SecretProviderException
     */
    public function getSecretValue(string $name, string $version = ''): array
    {
        try {
            $path = $version ? "/secrets/{$name}/{$version}" : "/secrets/{$name}";
            $response = $this->httpClient->get($path.'?api-version='.self::API_VERSION);
            $data = json_decode($response->getBody()->getContents(), true);

            // Extract secret name from ID URL: https://vault.azure.net/secrets/{name}/{version}
            $secretName = $name;
            if (isset($data['id'])) {
                $segments = explode('/', trim(parse_url($data['id'], PHP_URL_PATH), '/'));
                // Path is: secrets/{name} or secrets/{name}/{version}
                if (count($segments) >= 2 && $segments[0] === 'secrets') {
                    $secretName = $segments[1];
                }
            }

            return [
                'name' => $secretName,
                'value' => $data['value'] ?? '',
            ];
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to get Azure Key Vault secret: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve the access token using configured authentication method.
     *
     * @throws SecretProviderException
     */
    protected function resolveAccessToken(): string
    {
        // Option 1: Direct access token
        $token = Config::get('scrubber.secret_manager.providers.azure.access_token');
        if (! empty($token)) {
            return $token;
        }

        // Option 2: Managed Identity (auto-detected in Azure)
        if ($this->isManagedIdentityAvailable()) {
            return $this->getManagedIdentityToken();
        }

        // Option 3: Client credentials flow
        $tenantId = Config::get('scrubber.secret_manager.providers.azure.tenant_id');
        $clientId = Config::get('scrubber.secret_manager.providers.azure.client_id');
        $clientSecret = Config::get('scrubber.secret_manager.providers.azure.client_secret');

        if (! empty($tenantId) && ! empty($clientId) && ! empty($clientSecret)) {
            return $this->getClientCredentialsToken($tenantId, $clientId, $clientSecret);
        }

        throw new SecretProviderException(
            'Azure Key Vault authentication not configured. '.
            'Provide access_token, managed identity, or client credentials (tenant_id, client_id, client_secret).'
        );
    }

    /**
     * Check if Azure Managed Identity is available.
     */
    protected function isManagedIdentityAvailable(): bool
    {
        // Azure App Service, Functions, Container Apps
        if (! empty(getenv('IDENTITY_ENDPOINT')) && ! empty(getenv('IDENTITY_HEADER'))) {
            return true;
        }

        // Azure VM, VMSS (IMDS)
        if (! empty(getenv('MSI_ENDPOINT')) || $this->isRunningOnAzureVm()) {
            return true;
        }

        return false;
    }

    /**
     * Check if running on Azure VM by attempting to reach IMDS.
     */
    protected function isRunningOnAzureVm(): bool
    {
        // Skip IMDS check in testing environments
        if (app()->runningUnitTests()) {
            return false;
        }

        try {
            $client = new Client(['timeout' => 1]);
            $client->get('http://169.254.169.254/metadata/instance?api-version=2021-02-01', [
                'headers' => ['Metadata' => 'true'],
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get access token using Managed Identity.
     *
     * @throws SecretProviderException
     */
    protected function getManagedIdentityToken(): string
    {
        try {
            // Azure App Service, Functions, Container Apps
            $endpoint = getenv('IDENTITY_ENDPOINT');
            $header = getenv('IDENTITY_HEADER');

            if ($endpoint && $header) {
                $client = new Client(['timeout' => 10]);
                $response = $client->get($endpoint, [
                    'headers' => ['X-IDENTITY-HEADER' => $header],
                    'query' => [
                        'api-version' => '2019-08-01',
                        'resource' => 'https://vault.azure.net',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                if (! isset($data['access_token'])) {
                    throw new SecretProviderException('Invalid response from Azure Managed Identity endpoint: missing access_token');
                }

                return $data['access_token'];
            }

            // Azure VM/VMSS (IMDS)
            $client = new Client(['timeout' => 5]);
            $response = $client->get('http://169.254.169.254/metadata/identity/oauth2/token', [
                'headers' => ['Metadata' => 'true'],
                'query' => [
                    'api-version' => '2018-02-01',
                    'resource' => 'https://vault.azure.net',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (! isset($data['access_token'])) {
                throw new SecretProviderException('Invalid response from Azure IMDS endpoint: missing access_token');
            }

            return $data['access_token'];
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to get Managed Identity token: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Get access token using client credentials flow.
     *
     * @throws SecretProviderException
     */
    protected function getClientCredentialsToken(string $tenantId, string $clientId, string $clientSecret): string
    {
        try {
            $client = new Client(['timeout' => 10]);
            $response = $client->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://vault.azure.net/.default',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (! isset($data['access_token'])) {
                throw new SecretProviderException('Invalid response from Azure OAuth endpoint: missing access_token');
            }

            return $data['access_token'];
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to get Azure client credentials token: '.$e->getMessage(), 0, $e);
        }
    }
}
