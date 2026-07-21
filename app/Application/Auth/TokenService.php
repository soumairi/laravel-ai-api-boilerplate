<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

final readonly class TokenService
{
    public function __construct(private JWTAuth $jwt) {}

    public function issueAccessToken(User $user): string
    {
        return $this->jwt->claims(TokenClaims::forUser($user)->toArray())->fromUser($user);
    }

    public function ttlSeconds(): int
    {
        return (int) config('jwt.ttl', 15) * 60;
    }

    public function invalidateCurrent(): void
    {
        $this->jwt->parseToken()->invalidate();
    }
}
