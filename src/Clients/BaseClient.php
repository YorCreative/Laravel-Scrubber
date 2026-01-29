<?php

namespace YorCreative\Scrubber\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use YorCreative\Scrubber\Exceptions\SecretProviderException;

abstract class BaseClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     *
     * @throws GuzzleException
     * @throws SecretProviderException
     */
    public function get(string $path)
    {
        $data = json_decode($this->getClient()->get($path)->getBody()->getContents(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SecretProviderException('Invalid JSON response: '.json_last_error_msg());
        }

        return $data;
    }

    protected function getClient()
    {
        return $this->client;
    }
}
