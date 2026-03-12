<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

/**
 * Validates that a value matches a specific date format.
 *
 * Usage: date_format:Y-m-d
 */
final class DateFormat implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value) || !isset($parameters[0])) {
            return false;
        }

        $format = $parameters[0];
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);

        return $date !== false && $date->format($format) === $value;
    }

    public function message(string $attribute, array $parameters = []): string
    {
        $format = $parameters[0] ?? 'unknown';

        return sprintf('The %s does not match the format %s.', $attribute, $format);
    }
}
