<?php

declare(strict_types=1);

namespace Eymen\Queue;

final class QueueManager implements QueueInterface
{
    private ?QueueInterface $driver = null;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function push(Job $job, ?string $queue = null): string|int
    {
        return $this->driver()->push($job, $queue);
    }

    public function later(int $delay, Job $job, ?string $queue = null): string|int
    {
        return $this->driver()->later($delay, $job, $queue);
    }

    public function pop(?string $queue = null): ?array
    {
        return $this->driver()->pop($queue);
    }

    public function size(?string $queue = null): int
    {
        return $this->driver()->size($queue);
    }

    public function delete(string|int $id, ?string $queue = null): bool
    {
        return $this->driver()->delete($id, $queue);
    }

    public function release(string|int $id, int $delay = 0, ?string $queue = null): bool
    {
        return $this->driver()->release($id, $delay, $queue);
    }

    public function clear(?string $queue = null): int
    {
        return $this->driver()->clear($queue);
    }

    public function getDriverName(): string
    {
        return $this->config['driver'] ?? 'sync';
    }

    private function driver(): QueueInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $this->driver = match ($this->getDriverName()) {
            'sync' => new Drivers\SyncDriver(),
            'database' => new Drivers\DatabaseDriver(
                $this->config['connection'],
                $this->config['table'] ?? 'jobs',
                $this->config['queue'] ?? 'default',
            ),
            default => throw new \RuntimeException("Unsupported queue driver: {$this->getDriverName()}"),
        };

        return $this->driver;
    }
}
