<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

final class BooleanRule implements RuleInterface
{
    private const ACCEPTABLE = [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no'];

    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        return in_array($value, self::ACCEPTABLE, true);
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return "The {$attribute} field must be true or false.";
    }
}
