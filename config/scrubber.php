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
