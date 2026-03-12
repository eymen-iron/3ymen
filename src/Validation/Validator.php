<?php

declare(strict_types=1);

namespace Eymen\Validation;

use Eymen\Validation\Rules\Custom;

/**
 * Data validator.
 *
 * Validates data arrays against a set of rules, supporting built-in rules,
 * custom closures, and extensible rule classes. Rules are specified as
 * pipe-delimited strings (e.g., 'required|string|max:255') or arrays.
 */
final class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string|array<int, string|RuleInterface>> */
    private array $rules;

    /** @var array<string, string> */
    private array $messages;

    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /** @var array<string, \Closure> */
    private static array $customRules = [];

    /** @var array<string, string> */
    private static array $customMessages = [];

    /** @var array<string, class-string<RuleInterface>> */
    private static array $ruleMap = [
        'required' => Rules\Required::class,
        'email' => Rules\Email::class,
        'string' => Rules\StringRule::class,
        'numeric' => Rules\Numeric::class,
        'integer' => Rules\Integer::class,
        'min' => Rules\Min::class,
        'max' => Rules\Max::class,
        'between' => Rules\Between::class,
        'in' => Rules\In::class,
        'not_in' => Rules\NotIn::class,
        'confirmed' => Rules\Confirmed::class,
        'url' => Rules\Url::class,
        'ip' => Rules\Ip::class,
        'date' => Rules\Date::class,
        'date_format' => Rules\DateFormat::class,
        'regex' => Rules\Regex::class,
        'alpha' => Rules\Alpha::class,
        'alpha_num' => Rules\AlphaNum::class,
        'alpha_dash' => Rules\AlphaDash::class,
        'array' => Rules\ArrayRule::class,
        'boolean' => Rules\BooleanRule::class,
        'json' => Rules\Json::class,
        'nullable' => Rules\Nullable::class,
        'sometimes' => Rules\Sometimes::class,
        'unique' => Rules\Unique::class,
        'exists' => Rules\Exists::class,
    ];

    /**
     * @param array<string, mixed> $data The data to validate
     * @param array<string, string|array<int, string|RuleInterface>> $rules Validation rules
     * @param array<string, string> $messages Custom error messages
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Create a new validator instance.
     *
     * @param array<string, mixed> $data The data to validate
     * @param array<string, string|array<int, string|RuleInterface>> $rules Validation rules
     * @param array<string, string> $messages Custom error messages
     */
    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    /**
     * Run the validator's rules against its data and return validated data.
     *
     * @return array<string, mixed> The validated data
     *
     * @throws ValidationException If validation fails
     */
    public function validate(): array
    {
        $this->errors = [];

        foreach ($this->rules as $attribute => $rules) {
            $parsedRules = $this->parseRules($rules);
            $value = $this->getValue($attribute);

            // Check 'sometimes': skip if the attribute is not present at all
            if ($this->hasSometimesRule($parsedRules) && !$this->hasAttribute($attribute)) {
                continue;
            }

            // Check 'nullable': stop validation if the value is null
            if ($this->hasNullableRule($parsedRules) && $value === null) {
                continue;
            }

            $this->validateAttribute($attribute, $value, $parsedRules);
        }

        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }

        return $this->validated();
    }

    /**
     * Determine if the data fails the validation rules.
     */
    public function fails(): bool
    {
        if ($this->errors === []) {
            try {
                $this->validate();
            } catch (ValidationException) {
                // errors are already set
            }
        }

        return $this->errors !== [];
    }

    /**
     * Determine if the data passes the validation rules.
     */
    public function passes(): bool
    {
        return !$this->fails();
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        if ($this->errors === []) {
            try {
                $this->validate();
            } catch (ValidationException) {
                // errors are already set
            }
        }

        return $this->errors;
    }

    /**
     * Get the validated data (only fields that had rules).
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $validated = [];

        foreach ($this->rules as $attribute => $rules) {
            if ($this->hasAttribute($attribute)) {
                $validated[$attribute] = $this->getValue($attribute);
            }
        }

        return $validated;
    }

    /**
     * Extend the validator with a custom rule.
     *
     * @param string $name The rule name
     * @param \Closure $callback The validation callback: fn(string $attribute, mixed $value, array $params, array $data): bool
     * @param string $message The default error message
     */
    public static function extend(string $name, \Closure $callback, string $message = ''): void
    {
        self::$customRules[$name] = $callback;

        if ($message !== '') {
            self::$customMessages[$name] = $message;
        }
    }

    /**
     * Set a database connection for unique/exists rules.
     *
     * @param \Eymen\Database\Connection $connection
     */
    public function setConnection(\Eymen\Database\Connection $connection): void
    {
        Rules\Unique::setConnection($connection);
        Rules\Exists::setConnection($connection);
    }

    /**
     * Parse rules from string or array format.
     *
     * @param string|array<int, string|RuleInterface> $rules
     * @return array<int, array{rule: string|RuleInterface, parameters: array<int, string>}>
     */
    private function parseRules(string|array $rules): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $parsed = [];

        foreach ($rules as $rule) {
            if ($rule instanceof RuleInterface) {
                $parsed[] = ['rule' => $rule, 'parameters' => []];
                continue;
            }

            if (!is_string($rule) || $rule === '') {
                continue;
            }

            // Handle regex rule specially since it may contain colons and pipes
            if (str_starts_with($rule, 'regex:')) {
                $parsed[] = ['rule' => 'regex', 'parameters' => [substr($rule, 6)]];
                continue;
            }

            $parts = explode(':', $rule, 2);
            $name = $parts[0];
            $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

            $parsed[] = ['rule' => $name, 'parameters' => $parameters];
        }

        return $parsed;
    }

    /**
     * Validate a single attribute against its rules.
     *
     * @param string $attribute The attribute name
     * @param mixed $value The attribute value
     * @param array<int, array{rule: string|RuleInterface, parameters: array<int, string>}> $rules
     */
    private function validateAttribute(string $attribute, mixed $value, array $rules): void
    {
        foreach ($rules as $ruleEntry) {
            $rule = $ruleEntry['rule'];
            $parameters = $ruleEntry['parameters'];

            // Skip meta-rules
            if (is_string($rule) && in_array($rule, ['nullable', 'sometimes'], true)) {
                continue;
            }

            // RuleInterface instances
            if ($rule instanceof RuleInterface) {
                if (!$rule->passes($attribute, $value, $parameters, $this->data)) {
                    $this->addError($attribute, $rule->message($attribute, $parameters));
                }
                continue;
            }

            // Custom closures registered via extend()
            if (isset(self::$customRules[$rule])) {
                $callback = self::$customRules[$rule];
                if (!$callback($attribute, $value, $parameters, $this->data)) {
                    $message = $this->getErrorMessage($attribute, $rule, $parameters);
                    $this->addError($attribute, $message);
                }
                continue;
            }

            // Built-in rules from the rule map
            if (!isset(self::$ruleMap[$rule])) {
                throw new \InvalidArgumentException(
                    sprintf('Validation rule [%s] is not defined.', $rule)
                );
            }

            $ruleClass = self::$ruleMap[$rule];
            $ruleInstance = new $ruleClass();

            if (!$ruleInstance->passes($attribute, $value, $parameters, $this->data)) {
                $message = $this->getErrorMessage($attribute, $rule, $parameters, $ruleInstance);
                $this->addError($attribute, $message);
            }
        }
    }

    /**
     * Add an error message for an attribute.
     */
    private function addError(string $attribute, string $message): void
    {
        $this->errors[$attribute][] = $message;
    }

    /**
     * Get the error message for a rule, checking custom messages first.
     *
     * @param string $attribute The attribute name
     * @param string $rule The rule name
     * @param array<int, string> $parameters
     * @param RuleInterface|null $ruleInstance
     */
    private function getErrorMessage(
        string $attribute,
        string $rule,
        array $parameters,
        ?RuleInterface $ruleInstance = null,
    ): string {
        // Check for attribute-specific custom messages: 'email.required'
        $key = $attribute . '.' . $rule;
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        // Check for rule-level custom messages: 'required'
        if (isset($this->messages[$rule])) {
            return $this->messages[$rule];
        }

        // Check for custom rule messages from extend()
        if (isset(self::$customMessages[$rule])) {
            return str_replace(':attribute', $attribute, self::$customMessages[$rule]);
        }

        // Use the rule's default message
        if ($ruleInstance !== null) {
            return $ruleInstance->message($attribute, $parameters);
        }

        return sprintf('The %s field is invalid.', $attribute);
    }

    /**
     * Get a value from the data array, supporting dot notation.
     */
    private function getValue(string $attribute): mixed
    {
        if (array_key_exists($attribute, $this->data)) {
            return $this->data[$attribute];
        }

        // Support dot notation for nested data
        $keys = explode('.', $attribute);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Check if an attribute exists in the data.
     */
    private function hasAttribute(string $attribute): bool
    {
        if (array_key_exists($attribute, $this->data)) {
            return true;
        }

        // Support dot notation
        $keys = explode('.', $attribute);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return false;
            }
            $value = $value[$key];
        }

        return true;
    }

    /**
     * Check if the parsed rules contain a 'sometimes' rule.
     *
     * @param array<int, array{rule: string|RuleInterface, parameters: array<int, string>}> $rules
     */
    private function hasSometimesRule(array $rules): bool
    {
        foreach ($rules as $entry) {
            if ($entry['rule'] === 'sometimes') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the parsed rules contain a 'nullable' rule.
     *
     * @param array<int, array{rule: string|RuleInterface, parameters: array<int, string>}> $rules
     */
    private function hasNullableRule(array $rules): bool
    {
        foreach ($rules as $entry) {
            if ($entry['rule'] === 'nullable') {
                return true;
            }
        }
        return false;
    }
}
