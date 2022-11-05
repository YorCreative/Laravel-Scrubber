<?php

return [
    'redaction' => '**redacted**',
    'secret_manager' => [
        'key' => env('APP_KEY', '44mfXzhGl4IiILZ844mfXzhGl4IiILZ8'),
        'cipher' => 'AES-256-CBC',
        'enabled' => false,
        'providers' => [
            'gitlab' => [
                'enabled' => false,
                'project_id' => env('GITLAB_PROJECT_ID', 'change_me'),
                'token' => env('GITLAB_TOKEN', 'change_me'),
                'host' => 'https://gitlab.com',
                'keys' => ['*'],
            ],
        ],
    ],
    'regex_loader' => ['*'],
    'tap_channels' => ['*'],
];
