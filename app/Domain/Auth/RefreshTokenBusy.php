<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use RuntimeException;

final class RefreshTokenBusy extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Refresh is already in progress.');
    }
}
