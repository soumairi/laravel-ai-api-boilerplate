<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use RuntimeException;

final class InvalidRefreshToken extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid refresh token.');
    }
}
