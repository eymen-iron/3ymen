<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;
use Eymen\Database\Connection;
use Eymen\Database\Migrator;

final class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run database migrations';

    public function handle(): int
    {
        $app = \Eymen\Foundation\Application::getInstance();
        $connection = $app->get(Connection::class);
        $path = $app->databasePath('migrations');

        $migrator = new Migrator($connection, $path);

        if ($this->hasOption('rollback')) {
            $steps = (int) ($this->option('steps') ?: 1);
            $rolled = $migrator->rollback($steps);

            if (empty($rolled)) {
                $this->info('Nothing to rollback.');
                return 0;
            }

            foreach ($rolled as $file) {
                $this->info("Rolled back: {$file}");
            }

            return 0;
        }

        if ($this->hasOption('reset')) {
            $rolled = $migrator->reset();

            if (empty($rolled)) {
                $this->info('Nothing to reset.');
                return 0;
            }

            foreach ($rolled as $file) {
                $this->info("Rolled back: {$file}");
            }

            return 0;
        }

        if ($this->hasOption('status')) {
            $statuses = $migrator->status();

            if (empty($statuses)) {
                $this->info('No migrations found.');
                return 0;
            }

            $this->table(
                ['Migration', 'Batch', 'Ran At'],
                array_map(fn($s) => [$s['migration'], (string) $s['batch'], $s['ran_at'] ?? 'Pending'], $statuses)
            );

            return 0;
        }

        $migrated = $migrator->run();

        if (empty($migrated)) {
            $this->info('Nothing to migrate.');
            return 0;
        }

        foreach ($migrated as $file) {
            $this->info("Migrated: {$file}");
        }

        return 0;
    }
}
