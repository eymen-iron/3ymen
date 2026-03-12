<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;

final class SendWelcomeNotification
{
    public function handle(UserRegistered $event, string $eventName): void
    {
        // Dispatch welcome email job or send notification
    }
}
