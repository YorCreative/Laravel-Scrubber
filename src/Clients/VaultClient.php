<?php

namespace YorCreative\Scrubber\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Exceptions\SecretProviderException;

/**
 * HashiCorp Vault client using REST API.
 *
 * Supports KV v1 and v2 secret engines via direct HTTP calls.
 */
class VaultClient
{
    protected Client $httpClient;

    protected string $host;

    protected string $token;

    protected string $engine;

    protected ?string $namespace;

    protected int $kvVersion;

    public function __construct(?Client $httpClient = null)
    {
        $this->host = Config::get('scrubber.secret_manager.providers.vault.host', 'http://127.0.0.1:8200');
        $this->token = Config::get('scrubber.secret_manager.providers.vault.token', '');
        $this->engine = Config::get('scrubber.secret_manager.providers.vault.engine', 'secret');
        $this->namespace = Config::get('scrubber.secret_manager.providers.vault.namespace');
        $this->kvVersion = (int) Config::get('scrubber.secret_manager.providers.vault.version', 2);

        $headers = [
            'X-Vault-Token' => $this->token,
            'Content-Type' => 'application/json',
        ];

        if ($this->namespace) {
            $headers['X-Vault-Namespace'] = $this->namespace;
        }

        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => rtrim($this->host, '/'),
            'headers' => $headers,
        ]);
    }

    /**
     * List secrets at a given path.
     *
     * @throws SecretProviderException
     */
    public function listSecrets(string $path = ''): array
    {
        try {
            $apiPath = $this->buildApiPath($path, true);

            $response = $this->httpClient->request('LIST', $apiPath);
            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SecretProviderException('Invalid JSON response from Vault: '.json_last_error_msg());
            }

            return $data['data']['keys'] ?? [];
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to list Vault secrets: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a secret at a given path.
     *
     * @throws SecretProviderException
     */
    public function getSecret(string $path): array
    {
        try {
            $apiPath = $this->buildApiPath($path, false);

            $response = $this->httpClient->get($apiPath);
            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SecretProviderException('Invalid JSON response from Vault: '.json_last_error_msg());
            }

            if ($this->kvVersion === 2) {
                return $data['data']['data'] ?? [];
            }

            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to get Vault secret: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Build the API path for Vault requests.
     */
    protected function buildApiPath(string $path, bool $forList = false): string
    {
        $basePath = Config::get('scrubber.secret_manager.providers.vault.path', '');
        $fullPath = trim($basePath.'/'.$path, '/');

        if ($this->kvVersion === 2) {
            $segment = $forList ? 'metadata' : 'data';

            return "/v1/{$this->engine}/{$segment}/{$fullPath}";
        }

        return "/v1/{$this->engine}/{$fullPath}";
    }
}
