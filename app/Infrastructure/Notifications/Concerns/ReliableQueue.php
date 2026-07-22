<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Concerns;

trait ReliableQueue
{
    public int $tries = 3;

    public int $timeout = 30;

    public bool $afterCommit = true;

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }
}
