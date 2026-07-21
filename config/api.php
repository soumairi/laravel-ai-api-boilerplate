<?php

declare(strict_types=1);

return [
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),
    'require_email_verification' => (bool) env('REQUIRE_EMAIL_VERIFICATION', true),
    'refresh_ttl' => (int) env('REFRESH_TOKEN_TTL', 43200),
    'max_request_bytes' => (int) env('API_MAX_REQUEST_BYTES', 1_048_576),
    'cors_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
    'password_reset_url' => env('FRONTEND_PASSWORD_RESET_URL', 'http://localhost/reset-password'),
    'rate_limits' => [
        'auth_per_minute' => (int) env('API_RATE_LIMIT_AUTH_PER_MINUTE', 10),
        'login_identity_per_minute' => (int) env('API_RATE_LIMIT_LOGIN_IDENTITY_PER_MINUTE', 5),
        'register_per_minute' => (int) env('API_RATE_LIMIT_REGISTER_PER_MINUTE', 3),
        'forgot_password_attempts' => (int) env('API_RATE_LIMIT_FORGOT_PASSWORD_ATTEMPTS', 3),
        'forgot_password_decay_minutes' => (int) env('API_RATE_LIMIT_FORGOT_PASSWORD_DECAY_MINUTES', 15),
        'refresh_per_minute' => (int) env('API_RATE_LIMIT_REFRESH_PER_MINUTE', 10),
        'public_per_minute' => (int) env('API_RATE_LIMIT_PUBLIC_PER_MINUTE', 60),
        'authenticated_per_minute' => (int) env('API_RATE_LIMIT_AUTHENTICATED_PER_MINUTE', 120),
        'administration_per_minute' => (int) env('API_RATE_LIMIT_ADMINISTRATION_PER_MINUTE', 60),
    ],
];
