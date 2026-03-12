<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value matches a regular expression pattern.
 *
 * Usage: regex:/^[a-zA-Z]+$/
 */
final class Regex implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        if (!isset($parameters[0])) {
            return false;
        }

        $pattern = $parameters[0];

        return preg_match($pattern, (string) $value) > 0;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s format is invalid.', $attribute);
    }
}
