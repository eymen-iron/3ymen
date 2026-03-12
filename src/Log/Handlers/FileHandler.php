<?php

declare(strict_types=1);

namespace Eymen\Log\Handlers;

use Eymen\Log\LogHandlerInterface;
use Eymen\Log\Logger;

/**
 * File-based log handler.
 *
 * Appends formatted log records to a file on disk. Automatically
 * creates the log file and parent directories when they do not exist.
 *
 * Output format:
 *   [2026-03-12 14:30:00] local.ERROR: Error message {"key": "value"}
 */
final class FileHandler implements LogHandlerInterface
{
    private string $path;

    private string $minimumLevel;

    /**
     * @param string $path Absolute path to the log file
     * @param string $minimumLevel Minimum level this handler processes
     */
    public function __construct(string $path, string $minimumLevel = Logger::DEBUG)
    {
        $this->path = $path;
        $this->minimumLevel = $minimumLevel;
    }

    public function handle(string $level, string $message, array $context = []): void
    {
        if (!$this->isHandling($level)) {
            return;
        }

        $this->ensureDirectory();

        $timestamp = date('Y-m-d H:i:s');
        $upperLevel = strtoupper($level);

        $contextString = '';

        // Filter out interpolated keys (already in message) and build context JSON
        if ($context !== []) {
            $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded !== false && $encoded !== '[]' && $encoded !== '{}') {
                $contextString = ' ' . $encoded;
            }
        }

        $line = "[{$timestamp}] local.{$upperLevel}: {$message}{$contextString}" . PHP_EOL;

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }

    public function isHandling(string $level): bool
    {
        return (Logger::LEVELS[$level] ?? 0) >= (Logger::LEVELS[$this->minimumLevel] ?? 0);
    }

    /**
     * Ensure the parent directory for the log file exists.
     */
    private function ensureDirectory(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
