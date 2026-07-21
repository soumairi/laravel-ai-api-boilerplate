<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Models\User;

final readonly class RotatedTokenPair
{
    public function __construct(
        public User $user,
        public TokenPair $tokenPair,
    ) {}
}
