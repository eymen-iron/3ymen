<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is numeric.
 */
final class Numeric implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        return is_numeric($value);
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must be a number.', $attribute);
    }
}
