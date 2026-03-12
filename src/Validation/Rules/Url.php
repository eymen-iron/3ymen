<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is a valid URL.
 *
 * Uses PHP's filter_var with FILTER_VALIDATE_URL.
 */
final class Url implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must be a valid URL.', $attribute);
    }
}
