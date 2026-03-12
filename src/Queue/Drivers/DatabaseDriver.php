<?php

declare(strict_types=1);

namespace Eymen\Queue\Drivers;

use Eymen\Database\Connection;
use Eymen\Queue\Job;
use Eymen\Queue\QueueInterface;

final class DatabaseDriver implements QueueInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $table = 'jobs',
        private readonly string $defaultQueue = 'default',
    ) {
    }

    public function push(Job $job, ?string $queue = null): string|int
    {
        $queue ??= $job->queue ?? $this->defaultQueue;
        $now = date('Y-m-d H:i:s');

        $this->connection->insert(
            "INSERT INTO {$this->table} (queue, payload, attempts, reserved_at, available_at, created_at) VALUES (?, ?, ?, NULL, ?, ?)",
            [$queue, json_encode($job, JSON_THROW_ON_ERROR), 0, $now, $now]
        );

        return $this->connection->lastInsertId();
    }

    public function later(int $delay, Job $job, ?string $queue = null): string|int
    {
        $queue ??= $job->queue ?? $this->defaultQueue;
        $availableAt = date('Y-m-d H:i:s', time() + $delay);
        $now = date('Y-m-d H:i:s');

        $this->connection->insert(
            "INSERT INTO {$this->table} (queue, payload, attempts, reserved_at, available_at, created_at) VALUES (?, ?, ?, NULL, ?, ?)",
            [$queue, json_encode($job, JSON_THROW_ON_ERROR), 0, $availableAt, $now]
        );

        return $this->connection->lastInsertId();
    }

    public function pop(?string $queue = null): ?array
    {
        $queue ??= $this->defaultQueue;
        $now = date('Y-m-d H:i:s');

        return $this->connection->transaction(function () use ($queue, $now) {
            $job = $this->connection->select(
                "SELECT * FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL AND available_at <= ? ORDER BY id ASC LIMIT 1",
                [$queue, $now]
            );

            if (empty($job)) {
                return null;
            }

            $job = $job[0];

            $this->connection->update(
                "UPDATE {$this->table} SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?",
                [$now, $job['id']]
            );

            return $job;
        });
    }

    public function size(?string $queue = null): int
    {
        $queue ??= $this->defaultQueue;

        $result = $this->connection->select(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL",
            [$queue]
        );

        return (int) ($result[0]['count'] ?? 0);
    }

    public function delete(string|int $id, ?string $queue = null): bool
    {
        $deleted = $this->connection->delete(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );

        return $deleted > 0;
    }

    public function release(string|int $id, int $delay = 0, ?string $queue = null): bool
    {
        $availableAt = date('Y-m-d H:i:s', time() + $delay);

        $updated = $this->connection->update(
            "UPDATE {$this->table} SET reserved_at = NULL, available_at = ? WHERE id = ?",
            [$availableAt, $id]
        );

        return $updated > 0;
    }

    public function clear(?string $queue = null): int
    {
        $queue ??= $this->defaultQueue;

        return $this->connection->delete(
            "DELETE FROM {$this->table} WHERE queue = ?",
            [$queue]
        );
    }

    public function createTable(): void
    {
        $driver = $this->connection->getDriver();

        $autoIncrement = match ($driver) {
            'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'pgsql' => 'BIGSERIAL PRIMARY KEY',
            default => 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        };

        $this->connection->statement("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id {$autoIncrement},
                queue VARCHAR(255) NOT NULL,
                payload LONGTEXT NOT NULL,
                attempts INTEGER UNSIGNED NOT NULL DEFAULT 0,
                reserved_at TIMESTAMP NULL,
                available_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP NOT NULL
            )
        ");

        if ($driver !== 'sqlite') {
            $this->connection->statement(
                "CREATE INDEX IF NOT EXISTS idx_{$this->table}_queue_reserved ON {$this->table} (queue, reserved_at, available_at)"
            );
        }
    }
}
