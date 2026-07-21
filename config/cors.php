<?php

declare(strict_types=1);

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Request-ID'],
    'exposed_headers' => ['X-Request-ID'],
    'max_age' => 600,
    'supports_credentials' => false,
];
