<?php

return [
    /**
     * Specify the string to use to redact the data
     */
    'redaction' => '**redacted**',

    'secret_manager' => [
        'key' => env('APP_KEY'),
        'cipher' => 'AES-256-CBC',
        'enabled' => false,
        'providers' => [
            'gitlab' => [
                /**
                 * Enable the GitLab secret manager
                 */
                'enabled' => false,
                'project_id' => env('GITLAB_PROJECT_ID'),
                'token' => env('GITLAB_TOKEN'),
                'host' => 'https://gitlab.com',
                /**
                 * `*` will grab all the secrets, if you want specific variables
                 * define the keys in an array
                 */
                'keys' => ['*'],
            ],
            'aws' => [
                /**
                 * Enable the AWS Secrets Manager provider
                 * Requires: composer require aws/aws-sdk-php
                 */
                'enabled' => false,
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'version' => 'latest',
                /**
                 * AWS credentials (optional - falls back to default credential chain)
                 * The SDK will automatically use:
                 * - Environment variables (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY)
                 * - IAM instance profile (EC2)
                 * - ECS task role
                 * - Web identity token (EKS)
                 */
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
                /**
                 * `*` will grab all secrets, or specify an array of secret names/ARNs
                 */
                'keys' => ['*'],
            ],
            'vault' => [
                /**
                 * Enable the HashiCorp Vault provider
                 * Uses REST API directly - no additional dependencies required
                 */
                'enabled' => false,
                'host' => env('VAULT_ADDR', 'http://127.0.0.1:8200'),
                'token' => env('VAULT_TOKEN'),
                /**
                 * Vault namespace (Enterprise feature, optional)
                 */
                'namespace' => env('VAULT_NAMESPACE'),
                /**
                 * KV secrets engine mount path
                 */
                'engine' => env('VAULT_ENGINE', 'secret'),
                /**
                 * Base path within the engine to read secrets from
                 */
                'path' => env('VAULT_PATH', ''),
                /**
                 * KV engine version (1 or 2)
                 */
                'version' => env('VAULT_KV_VERSION', 2),
                /**
                 * `*` will recursively grab all secrets at the path,
                 * or specify an array of specific secret paths
                 */
                'keys' => ['*'],
            ],
        ],
    ],

    /**
     * Specify the regexes to load
     * You can use a wildcard (*) to load all regexes in all `custom_regex_namespaces` and the default core regexes.
     * Otherwise, specify the regexes you want to load either by qualified class name or by unqualified (base) class name,
     * which will then search the `custom_regex_namespaces` and the default core regexes for a match.
     */
    'regex_loader' => ['*'],

    /**
     * Specify regex patterns to exclude from loading when using the regex loader
     * This allows fine-grained control over which regex patterns are loaded, especially useful when using wildcard (*) in regex_loader
     *
     * You can exclude patterns using any of these formats:
     * - Fully qualified class name (e.g., 'YorCreative\Scrubber\RegexCollection\GoogleApi')
     * - Base class name (e.g., 'GoogleApi', 'EmailAddress')
     * - Pattern constant from RegexCollection (e.g., RegexCollection::$GOOGLE_API)
     * - Custom namespace class (e.g., 'App\Scrubber\RegexCollection\HerokuApiKey')
     *
     * Example:
     * [
     *     'GoogleApi',
     *     'YorCreative\Scrubber\RegexCollection\EmailAddress',
     *     RegexCollection::$HEROKU_API_KEY,
     *     'App\Scrubber\RegexCollection\HerokuApiKey'
     * ]
     */
    'exclude_regex' => [],

    /**
     * Specify namespaces from which regexes will be loaded when using the wildcard (*)
     * for the regex_loader or where you use unqualified class names.
     */
    'custom_regex_namespaces' => [
        'App\\Scrubber\\RegexCollection',
    ],

    /**
     * Specify config keys for which the values will be scrubbed
     * You should use the dot notation to specify the keys
     * You can use wildcards (*) to match multiple keys
     *
     *  - 'database.connections.*.password'
     *  - 'app.secrets.*'
     *  - 'app.some.nested.key'
     */
    'config_loader' => [
        '*token',
        '*key',
        '*secret',
        '*password',
    ],

    /**
     * Specify the channels to tap into
     * You can use wildcards (*) to match multiple channels
     */
    'tap_channels' => false,
];
