<?php

return [
    'redaction' => '**redacted**',
    'secret_manager' => [
        'key' => env('APP_KEY', '44mfXzhGl4IiILZ844mfXzhGl4IiILZ8'),
        'cipher' => 'AES-256-CBC',
        'enabled' => true,
        'providers' => [
            'gitlab' => [
                'enabled' => true,
                'project_id' => env('GITLAB_PROJECT_ID', '23697932'),
                'token' => env('GITLAB_TOKEN', 'glpat-iWGMKLseAYZHWTM9idBd'),
                'host' => 'https://gitlab.com',
                'keys' => ['*'],
            ],
        ],
    ],
];
