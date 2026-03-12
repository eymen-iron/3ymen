<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a field has a matching confirmation field.
 *
 * The field must have a corresponding {field}_confirmation field
 * in the data with a matching value.
 *
 * Usage: confirmed (applied to 'password' checks 'password_confirmation')
 */
final class Confirmed implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        $confirmationKey = $attribute . '_confirmation';

        if (!array_key_exists($confirmationKey, $data)) {
            return false;
        }

        return $value === $data[$confirmationKey];
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s confirmation does not match.', $attribute);
    }
}
