<?php

namespace YorCreative\Scrubber\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Exceptions\SecretProviderException;

/**
 * Google Cloud Secret Manager client using REST API.
 *
 * Supports two authentication methods:
 * - Application Default Credentials (auto-detected in GCP environments)
 * - Direct access token
 */
class GoogleSecretManagerClient
{
    protected Client $httpClient;

    protected string $projectId;

    protected string $accessToken;

    protected const API_BASE = 'https://secretmanager.googleapis.com/v1';

    public function __construct(?Client $httpClient = null)
    {
        $this->projectId = Config::get('scrubber.secret_manager.providers.google.project_id', '');
        $this->accessToken = $this->resolveAccessToken();

        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => self::API_BASE,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * List all secrets in the project.
     *
     * @throws SecretProviderException
     */
    public function listSecrets(): array
    {
        try {
            $secrets = [];
            $pageToken = null;

            do {
                $query = ['pageSize' => 100];
                if ($pageToken) {
                    $query['pageToken'] = $pageToken;
                }

                $response = $this->httpClient->get("/projects/{$this->projectId}/secrets", [
                    'query' => $query,
                ]);
                $data = json_decode($response->getBody()->getContents(), true);

                foreach ($data['secrets'] ?? [] as $secret) {
                    $secrets[] = [
                        'name' => basename($secret['name']),
                        'fullName' => $secret['name'],
                    ];
                }

                $pageToken = $data['nextPageToken'] ?? null;
            } while ($pageToken);

            return $secrets;
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to list Google Cloud secrets: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the latest version of a secret.
     *
     * @throws SecretProviderException
     */
    public function getSecretValue(string $name, string $version = 'latest'): array
    {
        try {
            $response = $this->httpClient->get(
                "/projects/{$this->projectId}/secrets/{$name}/versions/{$version}:access"
            );
            $data = json_decode($response->getBody()->getContents(), true);

            // GCP returns base64-encoded payload
            $value = '';
            if (isset($data['payload']['data'])) {
                $decoded = base64_decode($data['payload']['data']);
                $value = $decoded !== false ? $decoded : '';
            }

            return [
                'name' => $name,
                'value' => $value,
            ];
        } catch (GuzzleException $e) {
            throw new SecretProviderException('Failed to get Google Cloud secret: '.$e->getMessage(), 0, $e);
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
        $token = Config::get('scrubber.secret_manager.providers.google.access_token');
        if (! empty($token)) {
            return $token;
        }

        // Option 2: Application Default Credentials (GCE, Cloud Run, GKE, Cloud Functions)
        if ($this->isRunningOnGcp()) {
            return $this->getMetadataToken();
        }

        throw new SecretProviderException(
            'Google Cloud Secret Manager authentication not configured. '.
            'Provide access_token or run in a GCP environment with Application Default Credentials.'
        );
    }

    /**
     * Check if running in a GCP environment by attempting to reach the metadata server.
     */
    protected function isRunningOnGcp(): bool
    {
        try {
            $client = new Client(['timeout' => 1]);
            $response = $client->get('http://metadata.google.internal/computeMetadata/v1/', [
                'headers' => ['Metadata-Flavor' => 'Google'],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get access token from GCP metadata server.
     *
     * @throws SecretProviderException
     */
    protected function getMetadataToken(): string
    {
        try {
            $client = new Client(['timeout' => 5]);
            $response = $client->get(
                'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token',
                [
                    'headers' => ['Metadata-Flavor' => 'Google'],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            if (! isset($data['access_token'])) {
                throw new SecretProviderException('Invalid response from GCP metadata server: missing access_token');
            }

            return $data['access_token'];
        } catch (GuzzleException $e) {
            throw new SecretProviderException(
                'Failed to get access token from GCP metadata server: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
