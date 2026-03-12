<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value contains only alphabetic characters.
 */
final class Alpha implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/\A[\pL\pM]+\z/u', $value) > 0;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return sprintf('The %s must only contain letters.', $attribute);
    }
}
