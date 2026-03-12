<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is between a minimum and maximum.
 *
 * For strings: checks string length.
 * For numerics: checks numeric value.
 * For arrays: checks element count.
 *
 * Usage: between:1,100
 */
final class Between implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!isset($parameters[0], $parameters[1])) {
            return false;
        }

        $min = (float) $parameters[0];
        $max = (float) $parameters[1];

        if (is_numeric($value)) {
            $numericValue = (float) $value;
            return $numericValue >= $min && $numericValue <= $max;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= (int) $min && $length <= (int) $max;
        }

        if (is_array($value)) {
            $count = count($value);
            return $count >= (int) $min && $count <= (int) $max;
        }

        return false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        $min = $parameters[0] ?? '0';
        $max = $parameters[1] ?? '0';

        return sprintf('The %s must be between %s and %s.', $attribute, $min, $max);
    }
}
