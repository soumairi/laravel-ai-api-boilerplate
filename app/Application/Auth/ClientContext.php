<?php

declare(strict_types=1);

namespace App\Application\Auth;

final readonly class ClientContext
{
    public function __construct(
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {}
}
