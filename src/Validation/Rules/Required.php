<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is present and not empty.
 *
 * A value is considered empty if it is null, an empty string,
 * or an empty array.
 */
final class Required implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && $value === []) {
            return false;
        }

        return true;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s field is required.', $attribute);
    }
}
