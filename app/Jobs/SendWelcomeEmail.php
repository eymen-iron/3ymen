<?php

declare(strict_types=1);

namespace App\Jobs;

use Eymen\Queue\Job;

final class SendWelcomeEmail extends Job
{
    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $userId = 0,
        public readonly string $email = '',
    ) {
    }

    public function handle(): void
    {
        // Send welcome email to user
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure
    }
}
