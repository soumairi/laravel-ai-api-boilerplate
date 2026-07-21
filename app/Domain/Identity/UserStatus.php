<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function mayAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
