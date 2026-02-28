<?php

declare(strict_types=1);

return [
    'api_key' => env('SENDKIT_API_KEY'),
    'api_url' => env('SENDKIT_API_URL', 'https://api.sendkit.com'),
    'webhook' => [
        'secret' => env('SENDKIT_WEBHOOK_SECRET'),
        'path' => env('SENDKIT_WEBHOOK_PATH', 'webhook/sendkit'),
        'tolerance' => 300,
    ],
];
