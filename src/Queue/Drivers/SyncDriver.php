<?php

declare(strict_types=1);

namespace Eymen\Queue\Drivers;

use Eymen\Queue\Job;
use Eymen\Queue\QueueInterface;

final class SyncDriver implements QueueInterface
{
    private int $lastId = 0;

    public function push(Job $job, ?string $queue = null): string|int
    {
        try {
            $job->handle();
        } catch (\Throwable $e) {
            $job->failed($e);
            throw $e;
        }

        return ++$this->lastId;
    }

    public function later(int $delay, Job $job, ?string $queue = null): string|int
    {
        return $this->push($job, $queue);
    }

    public function pop(?string $queue = null): ?array
    {
        return null;
    }

    public function size(?string $queue = null): int
    {
        return 0;
    }

    public function delete(string|int $id, ?string $queue = null): bool
    {
        return true;
    }

    public function release(string|int $id, int $delay = 0, ?string $queue = null): bool
    {
        return true;
    }

    public function clear(?string $queue = null): int
    {
        return 0;
    }
}
