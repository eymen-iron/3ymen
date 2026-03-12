<?php

declare(strict_types=1);

namespace Eymen\Log;

/**
 * Log handler contract.
 *
 * Each handler is responsible for writing log records to a specific
 * destination (file, stderr, etc.) and can filter by minimum severity level.
 */
interface LogHandlerInterface
{
    /**
     * Handle a log record.
     *
     * @param string $level The log level (e.g., Logger::ERROR)
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context data
     */
    public function handle(string $level, string $message, array $context = []): void;

    /**
     * Check if this handler handles a given log level.
     *
     * @param string $level The log level to check
     * @return bool True if the handler processes this level
     */
    public function isHandling(string $level): bool;
}
