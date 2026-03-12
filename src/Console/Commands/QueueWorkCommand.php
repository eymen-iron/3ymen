<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;
use Eymen\Queue\QueueManager;
use Eymen\Queue\Worker;

final class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Start processing jobs on the queue';

    public function handle(): int
    {
        $app = \Eymen\Foundation\Application::getInstance();
        $queue = $app->get(QueueManager::class);

        $worker = new Worker($queue);

        $queueName = $this->argument('queue') ?? 'default';
        $sleep = (int) ($this->option('sleep') ?? 3);
        $tries = (int) ($this->option('tries') ?? 3);
        $memory = (int) ($this->option('memory') ?? 128);
        $timeout = (int) ($this->option('timeout') ?? 60);

        $this->info("Processing jobs from the [{$queueName}] queue...");

        $worker->daemon($queueName, [
            'sleep' => $sleep,
            'tries' => $tries,
            'memory' => $memory,
            'timeout' => $timeout,
        ]);

        return 0;
    }
}
