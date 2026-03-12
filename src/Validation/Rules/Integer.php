<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is an integer.
 */
final class Integer implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must be an integer.', $attribute);
    }
}
