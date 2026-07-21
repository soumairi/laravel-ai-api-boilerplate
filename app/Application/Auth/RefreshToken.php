<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\TokenIdentifier;

final readonly class RefreshToken
{
    public function __construct(
        public TokenIdentifier $identifier,
        public string $expiresAt,
    ) {}
}
