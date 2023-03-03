<?php

namespace YorCreative\Scrubber\SecretManager\Providers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use YorCreative\Scrubber\Clients\GitLabClient;
use YorCreative\Scrubber\Exceptions\SecretProviderException;
use YorCreative\Scrubber\SecretManager\Secret;

class Gitlab implements SecretProviderInterface
{
    public static string $VARIABLES = '/api/v4/projects/{id}/variables';

    public static string $VARIABLE = '/api/v4/projects/{id}/variables/{key}';

    /**
     * @throws GuzzleException
     */
    public static function getSpecificSecret(string $key): Secret
    {
        $path = str_replace(
            '{key}',
            $key,
            str_replace(
                '{id}',
                Config::get('scrubber.secret_manager.providers.gitlab.project_id'),
                self::$VARIABLE
            )
        );

        $secret = self::retrieve($path);

        return new Secret($key, $secret['value']);
    }

    /**
     * @throws SecretProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function retrieve(string $path): array
    {
        try {
            return app(GitLabClient::class)->get($path);
        } catch (Exception $exception) {
            throw new SecretProviderException($exception->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     */
    public static function getAllSecrets(): Collection
    {
        $path = str_replace(
            '{id}',
            Config::get('scrubber.secret_manager.providers.gitlab.project_id'),
            self::$VARIABLES
        );

        $secrets = self::retrieve($path);
        $secretCollection = new Collection();

        foreach ($secrets as $variable) {
            $secretCollection->push(new Secret($variable['key'], $variable['value']));
        }

        return $secretCollection;
    }
}
