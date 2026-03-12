<?php

declare(strict_types=1);

namespace Eymen\Queue;

final class Worker
{
    private QueueInterface $queue;
    private bool $shouldQuit = false;
    private int $processed = 0;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    public function daemon(string $queue = 'default', array $options = []): void
    {
        $sleep = $options['sleep'] ?? 3;
        $tries = $options['tries'] ?? 3;
        $timeout = $options['timeout'] ?? 60;
        $memoryLimit = $options['memory'] ?? 128;

        while (!$this->shouldQuit) {
            $jobData = $this->queue->pop($queue);

            if ($jobData !== null) {
                $this->processJob($jobData, $tries);
                $this->processed++;
            } else {
                $this->sleep($sleep);
            }

            if ($this->checkMemory($memoryLimit)) {
                $this->stop();
            }
        }
    }

    public function processJob(array $jobData, int $maxTries = 3): void
    {
        try {
            $job = Job::fromArray(json_decode($jobData['payload'], true));

            $attempts = ($jobData['attempts'] ?? 0) + 1;

            if ($attempts > ($job->tries ?: $maxTries)) {
                $this->failJob($job, $jobData, new \RuntimeException(
                    "Job {$job->getDisplayName()} has exceeded max attempts ({$maxTries})"
                ));
                return;
            }

            $job->handle();

            if (isset($jobData['id'])) {
                $this->queue->delete($jobData['id'], $jobData['queue'] ?? null);
            }
        } catch (\Throwable $e) {
            $this->handleJobException($jobData, $e, $maxTries);
        }
    }

    public function stop(): void
    {
        $this->shouldQuit = true;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    private function handleJobException(array $jobData, \Throwable $e, int $maxTries): void
    {
        $attempts = ($jobData['attempts'] ?? 0) + 1;

        if ($attempts >= $maxTries) {
            try {
                $job = Job::fromArray(json_decode($jobData['payload'], true));
                $this->failJob($job, $jobData, $e);
            } catch (\Throwable) {
                // Could not deserialize job for failure handling
            }

            if (isset($jobData['id'])) {
                $this->queue->delete($jobData['id'], $jobData['queue'] ?? null);
            }

            return;
        }

        if (isset($jobData['id'])) {
            try {
                $job = Job::fromArray(json_decode($jobData['payload'], true));
                $delay = $job->retryAfter;
            } catch (\Throwable) {
                $delay = 60;
            }

            $this->queue->release($jobData['id'], $delay, $jobData['queue'] ?? null);
        }
    }

    private function failJob(Job $job, array $jobData, \Throwable $exception): void
    {
        try {
            $job->failed($exception);
        } catch (\Throwable) {
            // Swallow failure handler exceptions
        }
    }

    private function checkMemory(int $memoryLimit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    private function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
