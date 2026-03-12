<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value is not in a list of disallowed values.
 *
 * Usage: not_in:foo,bar,baz
 */
final class NotIn implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if ($parameters === []) {
            return true;
        }

        return !in_array((string) $value, $parameters, true);
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The selected %s is invalid.', $attribute);
    }
}
