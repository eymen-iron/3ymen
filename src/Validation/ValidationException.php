<?php

declare(strict_types=1);

namespace Eymen\Validation;

/**
 * Exception thrown when validation fails.
 *
 * Contains the validation errors organized by field name.
 */
final class ValidationException extends \RuntimeException
{
    /** @var array<string, array<int, string>> */
    private array $errors;

    /**
     * @param array<string, array<int, string>> $errors Validation errors by field
     */
    public function __construct(array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
