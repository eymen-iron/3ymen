<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;

final class Custom implements RuleInterface
{
    private \Closure $callback;
    private string $errorMessage;

    public function __construct(\Closure $callback, string $message = '')
    {
        $this->callback = $callback;
        $this->errorMessage = $message;
    }

    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        return ($this->callback)($attribute, $value, $parameters, $data);
    }

    public function message(string $attribute, array $parameters = []): string
    {
        if ($this->errorMessage !== '') {
            return str_replace(':attribute', $attribute, $this->errorMessage);
        }

        return "The {$attribute} field is invalid.";
    }
}
