<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is a valid IP address (IPv4 or IPv6).
 *
 * Uses PHP's filter_var with FILTER_VALIDATE_IP.
 */
final class Ip implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must be a valid IP address.', $attribute);
    }
}
