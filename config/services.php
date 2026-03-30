<?php
// config/services.php
return [
    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Custom suspension API (primary)
    'suspension_api' => [
        'url' => env('SUSPENSION_API_URL'),
        'key' => env('SUSPENSION_API_KEY'),
    ],

    // KAF (suspension API)
    'kaf' => [
        'url' => env('KAF_URL', 'https://api.kaf.example.com'),
        'key' => env('KAF_KEY'),
    ],

    // Iway (fallback suspension API)
    'iway' => [
        'url' => env('IWAY_URL', 'https://api.iway.example.com'),
        'key' => env('IWAY_KEY'),
    ],

    // Accounting notification email
    'accounting_email' => env('ACCOUNTING_EMAIL', 'comptabilite@bloosat.com'),
];
