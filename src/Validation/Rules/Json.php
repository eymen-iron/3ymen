<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

final class Json implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return "The {$attribute} must be a valid JSON string.";
    }
}
