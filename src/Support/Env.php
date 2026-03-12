<?php

declare(strict_types=1);

namespace Eymen\Support;

/**
 * Environment variable loader and accessor.
 *
 * Parses .env files and makes variables available via $_ENV, $_SERVER, and putenv().
 * Supports comments, empty lines, quoted values, variable interpolation, and export prefixes.
 */
final class Env
{
    /** @var array<string, string> Loaded environment variables */
    private static array $loaded = [];

    /** @var bool Whether an .env file has been loaded */
    private static bool $initialized = false;

    /**
     * Load environment variables from a .env file.
     *
     * @param string $path Absolute path to the .env file
     * @throws \RuntimeException If the file cannot be read
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException("Environment file not found or not readable: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Failed to read environment file: {$path}");
        }

        $lines = explode("\n", $contents);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Remove optional "export " prefix
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }

            // Must contain an = sign
            $separatorPos = strpos($line, '=');

            if ($separatorPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPos));
            $value = trim(substr($line, $separatorPos + 1));

            if ($key === '') {
                continue;
            }

            $singleQuoted = str_starts_with($value, "'") && str_ends_with($value, "'");
            $value = self::parseValue($value);

            // Perform variable interpolation only for non-single-quoted values
            if (!$singleQuoted) {
                $value = self::interpolate($value);
            }

            self::setVariable($key, $value);
        }

        self::$initialized = true;
    }

    /**
     * Get an environment variable value.
     *
     * @param string $key The variable name
     * @param mixed $default Default value if variable is not set
     * @return mixed The variable value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Check our loaded cache first
        if (array_key_exists($key, self::$loaded)) {
            return self::castValue(self::$loaded[$key]);
        }

        // Check $_ENV
        if (array_key_exists($key, $_ENV)) {
            return self::castValue((string) $_ENV[$key]);
        }

        // Check $_SERVER
        if (array_key_exists($key, $_SERVER)) {
            return self::castValue((string) $_SERVER[$key]);
        }

        // Check getenv()
        $value = getenv($key);

        if ($value !== false) {
            return self::castValue($value);
        }

        return $default;
    }

    /**
     * Check if an environment variable is defined.
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$loaded)
            || array_key_exists($key, $_ENV)
            || array_key_exists($key, $_SERVER)
            || getenv($key) !== false;
    }

    /**
     * Reset all loaded environment state (useful for testing).
     */
    public static function reset(): void
    {
        foreach (self::$loaded as $key => $value) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        self::$loaded = [];
        self::$initialized = false;
    }

    /**
     * Check if the environment has been initialized.
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Parse a raw value string, handling quotes and inline comments.
     */
    private static function parseValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // Handle double-quoted values
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
            // Process escape sequences in double-quoted strings
            $value = str_replace(
                ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                ["\n", "\r", "\t", '"', '\\'],
                $value
            );
            return $value;
        }

        // Handle single-quoted values (no escape processing)
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        }

        // Unquoted value: strip inline comments
        $commentPos = strpos($value, ' #');

        if ($commentPos !== false) {
            $value = trim(substr($value, 0, $commentPos));
        }

        return $value;
    }

    /**
     * Interpolate ${VAR_NAME} references in a value.
     */
    private static function interpolate(string $value): string
    {
        return preg_replace_callback('/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/', function (array $matches): string {
            $varName = $matches[1];

            if (array_key_exists($varName, self::$loaded)) {
                return self::$loaded[$varName];
            }

            $envValue = getenv($varName);

            return $envValue !== false ? $envValue : $matches[0];
        }, $value) ?? $value;
    }

    /**
     * Cast string values to their appropriate PHP types.
     */
    private static function castValue(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => is_numeric($value)
                ? (str_contains($value, '.') ? (float) $value : (int) $value)
                : $value,
        };
    }

    /**
     * Set a variable in all relevant stores.
     */
    private static function setVariable(string $key, string $value): void
    {
        self::$loaded[$key] = $value;
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}
