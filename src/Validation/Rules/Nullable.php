<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

final class Nullable implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        return true;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return '';
    }
}
