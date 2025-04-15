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
                'enabled' => false,
                'project_id' => env('GITLAB_PROJECT_ID'),
                'token' => env('GITLAB_TOKEN'),
                'host' => 'https://gitlab.com',
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
    'regex_collection_path_from_app' => 'Scrubber/RegexCollection',
];
