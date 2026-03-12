<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates minimum length (string) or minimum value (numeric).
 *
 * Usage: min:3
 */
final class Min implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $min = (float) $parameters[0];

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= (int) $min;
        }

        if (is_array($value)) {
            return count($value) >= (int) $min;
        }

        return false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        $min = $parameters[0] ?? '0';

        return sprintf('The %s must be at least %s.', $attribute, $min);
    }
}
