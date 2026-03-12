<?php

declare(strict_types=1);

namespace Eymen\Queue;

interface QueueInterface
{
    public function push(Job $job, ?string $queue = null): string|int;

    public function later(int $delay, Job $job, ?string $queue = null): string|int;

    public function pop(?string $queue = null): ?array;

    public function size(?string $queue = null): int;

    public function delete(string|int $id, ?string $queue = null): bool;

    public function release(string|int $id, int $delay = 0, ?string $queue = null): bool;

    public function clear(?string $queue = null): int;
}
