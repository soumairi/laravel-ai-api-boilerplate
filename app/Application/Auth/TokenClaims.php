<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Models\User;

final readonly class TokenClaims
{
    public function __construct(
        public string $issuer,
        public string $audience,
        public int $tokenVersion,
        public string $status,
    ) {}

    public static function forUser(User $user): self
    {
        return new self(
            issuer: (string) config('jwt.issuer'),
            audience: (string) config('jwt.audience'),
            tokenVersion: $user->token_version,
            status: $user->status->value,
        );
    }

    /** @return array{iss: string, aud: string, ver: int, status: string} */
    public function toArray(): array
    {
        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'ver' => $this->tokenVersion,
            'status' => $this->status,
        ];
    }
}
