<?php

declare(strict_types=1);

namespace App\Events;

use Eymen\Events\EventInterface;

final class UserRegistered implements EventInterface
{
    public function __construct(
        public readonly array $user,
        public readonly \DateTimeImmutable $registeredAt = new \DateTimeImmutable(),
    ) {
    }
}
