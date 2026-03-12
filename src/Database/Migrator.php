<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Migration runner and manager.
 *
 * Tracks which migrations have been applied, runs pending migrations,
 * and supports rolling back applied migrations. Migration state is
 * stored in a dedicated migrations table.
 */
final class Migrator
{
    private Connection $connection;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    /**
     * @param Connection $connection The database connection
     * @param string $migrationsPath Path to the directory containing migration files
     */
    public function __construct(Connection $connection, string $migrationsPath)
    {
        $this->connection = $connection;
        $this->migrationsPath = rtrim($migrationsPath, '/\\');
    }

    /**
     * Run all pending migrations.
     *
     * @return array<int, string> List of migrated file names
     */
    public function run(): array
    {
        $this->ensureMigrationsTable();

        $pending = $this->getPendingMigrations();

        if ($pending === []) {
            return [];
        }

        $batch = $this->getNextBatchNumber();
        $migrated = [];

        foreach ($pending as $file) {
            $migration = $this->resolve($file);
            $this->runMigration($migration, $file, $batch);
            $migrated[] = $file;
        }

        return $migrated;
    }

    /**
     * Roll back the last batch of migrations (or a specific number of steps).
     *
     * @param int $steps Number of batches to roll back
     * @return array<int, string> List of rolled-back file names
     */
    public function rollback(int $steps = 1): array
    {
        $this->ensureMigrationsTable();

        $ranMigrations = $this->getRanMigrations();

        if ($ranMigrations === []) {
            return [];
        }

        // Group by batch and take the last N batches
        $batches = [];
        foreach ($ranMigrations as $record) {
            $batches[(int) $record['batch']][] = $record['migration'];
        }

        krsort($batches);

        $rolledBack = [];
        $stepsProcessed = 0;

        foreach ($batches as $batch => $files) {
            if ($stepsProcessed >= $steps) {
                break;
            }

            // Roll back in reverse order within the batch
            $files = array_reverse($files);

            foreach ($files as $file) {
                $migration = $this->resolve($file);
                $migration->setConnection($this->connection);

                $this->connection->transaction(function () use ($migration, $file): void {
                    $migration->down();

                    $this->connection->delete(
                        sprintf('DELETE FROM %s WHERE migration = ?', $this->migrationsTable),
                        [$file]
                    );
                });

                $rolledBack[] = $file;
            }

            $stepsProcessed++;
        }

        return $rolledBack;
    }

    /**
     * Roll back all migrations.
     *
     * @return array<int, string> List of rolled-back file names
     */
    public function reset(): array
    {
        $this->ensureMigrationsTable();

        $ranMigrations = $this->getRanMigrations();

        if ($ranMigrations === []) {
            return [];
        }

        // Count distinct batches
        $batchCount = count(array_unique(array_column($ranMigrations, 'batch')));

        return $this->rollback($batchCount);
    }

    /**
     * Get the status of all known migrations.
     *
     * @return array<int, array{name: string, batch: int|null, ran_at: string|null}> Migration status records
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();

        $ranMigrations = $this->getRanMigrations();
        $ranMap = [];

        foreach ($ranMigrations as $record) {
            $ranMap[$record['migration']] = [
                'batch' => (int) $record['batch'],
                'ran_at' => $record['ran_at'] ?? null,
            ];
        }

        // Get all migration files
        $allFiles = $this->getAllMigrationFiles();

        $status = [];

        foreach ($allFiles as $file) {
            $info = $ranMap[$file] ?? null;
            $status[] = [
                'name' => $file,
                'batch' => $info['batch'] ?? null,
                'ran_at' => $info['ran_at'] ?? null,
            ];
        }

        return $status;
    }

    /**
     * Ensure the migrations tracking table exists.
     */
    private function ensureMigrationsTable(): void
    {
        if ($this->connection->tableExists($this->migrationsTable)) {
            return;
        }

        $schema = new Schema($this->connection);

        $schema->create($this->migrationsTable, function (Blueprint $table): void {
            $table->id();
            $table->string('migration')->unique();
            $table->integer('batch');
            $table->timestamp('ran_at')->nullable()->useCurrent();
        });
    }

    /**
     * Get all migration files that have not yet been run.
     *
     * @return array<int, string> Pending migration file names (without path)
     */
    private function getPendingMigrations(): array
    {
        $allFiles = $this->getAllMigrationFiles();
        $ranMigrations = array_column($this->getRanMigrations(), 'migration');

        return array_values(array_diff($allFiles, $ranMigrations));
    }

    /**
     * Get all migration records that have been run.
     *
     * @return array<int, array{migration: string, batch: int|string, ran_at: string|null}> Migration records
     */
    private function getRanMigrations(): array
    {
        try {
            return $this->connection->select(
                sprintf('SELECT migration, batch, ran_at FROM %s ORDER BY batch ASC, migration ASC', $this->migrationsTable)
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resolve a migration file name to a Migration instance.
     *
     * @param string $file Migration file name (without directory path)
     * @return Migration The migration instance
     *
     * @throws \RuntimeException If the file does not exist or does not contain a valid Migration
     */
    private function resolve(string $file): Migration
    {
        $path = $this->migrationsPath . '/' . $file;

        if (!is_file($path)) {
            throw new \RuntimeException(
                sprintf('Migration file not found: %s', $path)
            );
        }

        $instance = require $path;

        if (!$instance instanceof Migration) {
            throw new \RuntimeException(
                sprintf('Migration file must return an instance of %s: %s', Migration::class, $file)
            );
        }

        return $instance;
    }

    /**
     * Run a single migration and record it in the migrations table.
     *
     * @param Migration $migration The migration instance
     * @param string $file The migration file name
     * @param int $batch The current batch number
     */
    private function runMigration(Migration $migration, string $file, int $batch): void
    {
        $migration->setConnection($this->connection);

        $this->connection->transaction(function () use ($migration, $file, $batch): void {
            $migration->up();

            $this->connection->insert(
                sprintf('INSERT INTO %s (migration, batch) VALUES (?, ?)', $this->migrationsTable),
                [$file, $batch]
            );
        });
    }

    /**
     * Get all migration file names from the migrations directory.
     *
     * @return array<int, string> Sorted file names
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = [];

        /** @var \DirectoryIterator $fileInfo */
        foreach (new \DirectoryIterator($this->migrationsPath) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $filename = $fileInfo->getFilename();

            if (str_ends_with($filename, '.php')) {
                $files[] = $filename;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Get the next batch number.
     */
    private function getNextBatchNumber(): int
    {
        $result = $this->connection->select(
            sprintf('SELECT MAX(batch) AS max_batch FROM %s', $this->migrationsTable)
        );

        $maxBatch = $result[0]['max_batch'] ?? 0;

        return ((int) $maxBatch) + 1;
    }
}
