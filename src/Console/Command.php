<?php

declare(strict_types=1);

namespace Eymen\Console;

/**
 * Abstract base command class.
 *
 * Provides argument/option parsing, colored output helpers,
 * and interactive prompts for CLI commands.
 */
abstract class Command
{
    /** @var string The command name (e.g., 'serve', 'route:list') */
    protected string $name = '';

    /** @var string Human-readable description of the command */
    protected string $description = '';

    /**
     * Positional argument definitions.
     *
     * @var array<string, array{description: string, required?: bool, default?: string}>
     */
    protected array $arguments = [];

    /**
     * Option definitions.
     *
     * @var array<string, array{description: string, default?: mixed, shortcut?: string}>
     */
    protected array $options = [];

    /** @var array<string, string> Parsed positional argument values */
    private array $inputArguments = [];

    /** @var array<string, mixed> Parsed option values */
    private array $inputOptions = [];

    /**
     * Execute the command logic.
     *
     * @return int Exit code (0 = success, non-zero = failure)
     */
    abstract public function handle(): int;

    // ========================================================================
    // Input Accessors
    // ========================================================================

    /**
     * Get a positional argument value.
     */
    protected function argument(string $name): ?string
    {
        return $this->inputArguments[$name] ?? null;
    }

    /**
     * Get an option value.
     */
    protected function option(string $name): mixed
    {
        if (array_key_exists($name, $this->inputOptions)) {
            return $this->inputOptions[$name];
        }

        // Return defined default if available
        if (isset($this->options[$name]['default'])) {
            return $this->options[$name]['default'];
        }

        return null;
    }

    /**
     * Check if an option was explicitly provided.
     */
    protected function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->inputOptions);
    }

    // ========================================================================
    // Output Helpers
    // ========================================================================

    /**
     * Output an info message (green).
     */
    protected function info(string $message): void
    {
        $this->writeColored($message, '32');
    }

    /**
     * Output an error message (red).
     */
    protected function error(string $message): void
    {
        $this->writeColored($message, '31');
    }

    /**
     * Output a warning message (yellow).
     */
    protected function warn(string $message): void
    {
        $this->writeColored($message, '33');
    }

    /**
     * Output a plain message (no color).
     */
    protected function line(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    /**
     * Output one or more blank lines.
     */
    protected function newLine(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            fwrite(STDOUT, PHP_EOL);
        }
    }

    /**
     * Display an ASCII table.
     *
     * @param list<string> $headers Column headers
     * @param list<list<string>> $rows Table rows
     */
    protected function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = mb_strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $len = mb_strlen((string) $cell);
                if (!isset($widths[$i]) || $len > $widths[$i]) {
                    $widths[$i] = $len;
                }
            }
        }

        // Build separator line
        $separator = '+';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width + 2) . '+';
        }

        // Print header
        fwrite(STDOUT, $separator . PHP_EOL);

        $headerLine = '|';
        foreach ($headers as $i => $header) {
            $headerLine .= ' ' . str_pad($header, $widths[$i]) . ' |';
        }
        fwrite(STDOUT, $headerLine . PHP_EOL);
        fwrite(STDOUT, $separator . PHP_EOL);

        // Print rows
        foreach ($rows as $row) {
            $rowLine = '|';
            foreach ($widths as $i => $width) {
                $cell = (string) ($row[$i] ?? '');
                $rowLine .= ' ' . str_pad($cell, $width) . ' |';
            }
            fwrite(STDOUT, $rowLine . PHP_EOL);
        }

        fwrite(STDOUT, $separator . PHP_EOL);
    }

    /**
     * Ask a yes/no confirmation question.
     *
     * @param string $question The question to ask
     * @return bool True if the user answered yes
     */
    protected function confirm(string $question): bool
    {
        fwrite(STDOUT, $question . ' (y/n): ');

        $handle = fopen('php://stdin', 'r');

        if ($handle === false) {
            return false;
        }

        $answer = trim(fgets($handle) ?: '');
        fclose($handle);

        return in_array(strtolower($answer), ['y', 'yes'], true);
    }

    /**
     * Ask a question and return the answer.
     */
    protected function ask(string $question, string $default = ''): string
    {
        $prompt = $question;
        if ($default !== '') {
            $prompt .= " [{$default}]";
        }
        $prompt .= ': ';

        fwrite(STDOUT, $prompt);

        $handle = fopen('php://stdin', 'r');

        if ($handle === false) {
            return $default;
        }

        $answer = trim(fgets($handle) ?: '');
        fclose($handle);

        return $answer !== '' ? $answer : $default;
    }

    // ========================================================================
    // Internal Methods
    // ========================================================================

    /**
     * Set the parsed input from argv parsing.
     *
     * @param array<string, string> $arguments Parsed positional arguments
     * @param array<string, mixed> $options Parsed options
     */
    public function setInput(array $arguments, array $options): void
    {
        $this->inputArguments = $arguments;
        $this->inputOptions = $options;
    }

    /**
     * Get the command name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the command description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get argument definitions.
     *
     * @return array<string, array{description: string, required?: bool, default?: string}>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get option definitions.
     *
     * @return array<string, array{description: string, default?: mixed, shortcut?: string}>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Write a colored message to stdout.
     *
     * @param string $message The message text
     * @param string $colorCode ANSI color code (e.g., '32' for green)
     */
    private function writeColored(string $message, string $colorCode): void
    {
        $supportsColor = $this->supportsColor();

        if ($supportsColor) {
            fwrite(STDOUT, "\033[{$colorCode}m{$message}\033[0m" . PHP_EOL);
        } else {
            fwrite(STDOUT, $message . PHP_EOL);
        }
    }

    /**
     * Determine if the terminal supports ANSI color codes.
     */
    private function supportsColor(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (getenv('ANSICON') !== false)
                || (getenv('ConEmuANSI') === 'ON')
                || (getenv('TERM') === 'xterm');
        }

        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
}
