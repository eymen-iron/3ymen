<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is a string.
 */
final class StringRule implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        return is_string($value);
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must be a string.', $attribute);
    }
}
