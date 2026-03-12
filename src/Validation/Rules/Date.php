<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is a valid date.
 *
 * Uses strtotime to determine if the value can be parsed as a date.
 */
final class Date implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return strtotime((string) $value) !== false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must be a valid date.', $attribute);
    }
}
