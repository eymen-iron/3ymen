<?php

declare(strict_types=1);

namespace Eymen\Validation;

/**
 * Validation rule contract.
 *
 * Defines the interface for individual validation rules that can
 * be applied to data attributes during validation.
 */
interface RuleInterface
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute The attribute name being validated
     * @param mixed $value The value of the attribute
     * @param array<int, string> $parameters Rule parameters (e.g., min:3 passes ['3'])
     * @param array<string, mixed> $data The full data array being validated
     * @return bool Whether the rule passes
     */
    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool;

    /**
     * Get the validation error message.
     *
     * @param string $attribute The attribute name
     * @param array<int, string> $parameters Rule parameters
     * @return string The error message
     */
    public function message(string $attribute, array $parameters = []): string;
}
