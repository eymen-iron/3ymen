<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates maximum length (string) or maximum value (numeric).
 *
 * Usage: max:255
 */
final class Max implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $max = (float) $parameters[0];

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= (int) $max;
        }

        if (is_array($value)) {
            return count($value) <= (int) $max;
        }

        return false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        $max = $parameters[0] ?? '0';

        return sprintf('The %s must not exceed %s.', $attribute, $max);
    }
}
