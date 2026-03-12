<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is in a list of allowed values.
 *
 * Usage: in:foo,bar,baz
 */
final class In implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if ($parameters === []) {
            return false;
        }

        return in_array((string) $value, $parameters, true);
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The selected %s is invalid.', $attribute);
    }
}
