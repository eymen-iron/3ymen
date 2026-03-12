<?php

declare(strict_types=1);

namespace Eymen\Log\Handlers;

use Eymen\Log\LogHandlerInterface;
use Eymen\Log\Logger;

/**
 * STDERR log handler with optional ANSI color coding.
 *
 * Writes formatted log records to the standard error stream.
 * When running in a terminal that supports ANSI colors, messages
 * are color-coded by severity level for visual clarity.
 */
final class StderrHandler implements LogHandlerInterface
{
    private string $minimumLevel;

    /**
     * ANSI color codes for each log level.
     *
     * @var array<string, string>
     */
    private const LEVEL_COLORS = [
        Logger::EMERGENCY => "\033[97;41m", // White on red background
        Logger::ALERT     => "\033[91m",    // Bright red
        Logger::CRITICAL  => "\033[31m",    // Red
        Logger::ERROR     => "\033[31m",    // Red
        Logger::WARNING   => "\033[33m",    // Yellow
        Logger::NOTICE    => "\033[36m",    // Cyan
        Logger::INFO      => "\033[32m",    // Green
        Logger::DEBUG     => "\033[90m",    // Dark gray
    ];

    /** ANSI reset sequence */
    private const RESET = "\033[0m";

    /**
     * @param string $minimumLevel Minimum level this handler processes
     */
    public function __construct(string $minimumLevel = Logger::DEBUG)
    {
        $this->minimumLevel = $minimumLevel;
    }

    public function handle(string $level, string $message, array $context = []): void
    {
        if (!$this->isHandling($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $upperLevel = strtoupper($level);

        $contextString = '';

        if ($context !== []) {
            $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded !== false && $encoded !== '[]' && $encoded !== '{}') {
                $contextString = ' ' . $encoded;
            }
        }

        $line = "[{$timestamp}] local.{$upperLevel}: {$message}{$contextString}" . PHP_EOL;

        if ($this->supportsColor()) {
            $color = self::LEVEL_COLORS[$level] ?? '';
            $line = $color . $line . self::RESET;
        }

        fwrite(STDERR, $line);
    }

    public function isHandling(string $level): bool
    {
        return (Logger::LEVELS[$level] ?? 0) >= (Logger::LEVELS[$this->minimumLevel] ?? 0);
    }

    /**
     * Determine if the STDERR stream supports ANSI color output.
     *
     * Checks for terminal capability and respects the NO_COLOR convention.
     */
    private function supportsColor(): bool
    {
        // Respect NO_COLOR convention: https://no-color.org/
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        // Check if STDERR is a TTY
        if (!defined('STDERR')) {
            return false;
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty(STDERR);
        }

        // Fallback for environments without stream_isatty
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDERR);
        }

        return false;
    }
}
