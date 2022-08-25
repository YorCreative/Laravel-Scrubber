<?php

namespace YorCreative\Scrubber\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

abstract class BaseClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param  string  $path
     * @return mixed
     *
     * @throws GuzzleException
     */
    public function get(string $path)
    {
        return json_decode($this->getClient()->get($path)->getBody()->getContents(), true);
    }

    protected function getClient()
    {
        return $this->client;
    }
}
