<?php

declare(strict_types=1);

namespace App\Application\Auth;

final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public int $accessExpiresIn,
        public string $refreshToken,
        public string $refreshExpiresAt,
    ) {}
}
