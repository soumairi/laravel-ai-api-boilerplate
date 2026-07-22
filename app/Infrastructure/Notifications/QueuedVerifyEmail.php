<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Infrastructure\Notifications\Concerns\ReliableQueue;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

final class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use ReliableQueue;

    public string $queue = 'notifications';
}
