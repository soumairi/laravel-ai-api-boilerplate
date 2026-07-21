<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

final class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    public string $queue = 'notifications';
}
