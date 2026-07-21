<?php

declare(strict_types=1);

use App\Domain\Identity\UserStatus;

it('only permits active users to authenticate', function (): void {
    expect(UserStatus::Active->mayAuthenticate())->toBeTrue()
        ->and(UserStatus::Pending->mayAuthenticate())->toBeFalse()
        ->and(UserStatus::Suspended->mayAuthenticate())->toBeFalse();
});
