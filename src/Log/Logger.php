<?php

declare(strict_types=1);

namespace Eymen\Log;

/**
 * PSR-3 compatible logger.
 *
 * Supports multiple handlers, level-based filtering, context interpolation,
 * and all eight PSR-3 severity levels. Does not depend on psr/log; the
 * interface is self-contained within the framework.
 */
final class Logger
{
    /** System is unusable */
    public const EMERGENCY = 'emergency';

    /** Action must be taken immediately */
    public const ALERT = 'alert';

    /** Critical conditions */
    public const CRITICAL = 'critical';

    /** Runtime errors that do not require immediate action */
    public const ERROR = 'error';

    /** Exceptional occurrences that are not errors */
    public const WARNING = 'warning';

    /** Normal but significant events */
    public const NOTICE = 'notice';

    /** Informational messages */
    public const INFO = 'info';

    /** Detailed debug information */
    public const DEBUG = 'debug';

    /**
     * Log levels ordered by severity (highest to lowest).
     *
     * @var array<string, int>
     */
    public const LEVELS = [
        self::EMERGENCY => 7,
        self::ALERT     => 6,
        self::CRITICAL  => 5,
        self::ERROR     => 4,
        self::WARNING   => 3,
        self::NOTICE    => 2,
        self::INFO      => 1,
        self::DEBUG     => 0,
    ];

    /** @var list<LogHandlerInterface> */
    private array $handlers = [];

    private string $minimumLevel;

    /**
     * @param string $minimumLevel The minimum log level to process
     */
    public function __construct(string $minimumLevel = self::DEBUG)
    {
        $this->minimumLevel = $minimumLevel;
    }

    /**
     * Add a handler to the logger.
     *
     * @param LogHandlerInterface $handler The handler to add
     */
    public function addHandler(LogHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Log an emergency message. System is unusable.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log an alert. Action must be taken immediately.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log a critical condition.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log a runtime error.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log a warning.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log a notice.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log an informational message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log a message at an arbitrary level.
     *
     * @param string $level The log level
     * @param string $message The log message
     * @param array<string, mixed> $context Context data for interpolation
     * @throws \InvalidArgumentException If the level is invalid
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        if (!$this->isHandled($level)) {
            return;
        }

        $message = $this->interpolate($message, $context);

        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($level)) {
                $handler->handle($level, $message, $context);
            }
        }
    }

    /**
     * Interpolate context values into message placeholders.
     *
     * Replaces {key} placeholders with corresponding values from the context
     * array. Objects implementing __toString() are converted; other non-scalar
     * values are JSON-encoded.
     *
     * @param string $message The message with {placeholder} tokens
     * @param array<string, mixed> $context The context values
     * @return string The interpolated message
     */
    private function interpolate(string $message, array $context): string
    {
        if (!str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];

        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';

            if (!str_contains($message, $placeholder)) {
                continue;
            }

            if ($value === null) {
                $replacements[$placeholder] = 'null';
            } elseif (is_bool($value)) {
                $replacements[$placeholder] = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $replacements[$placeholder] = (string) $value;
            } elseif ($value instanceof \DateTimeInterface) {
                $replacements[$placeholder] = $value->format(\DateTimeInterface::RFC3339);
            } elseif ($value instanceof \Stringable || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements[$placeholder] = (string) $value;
            } else {
                $replacements[$placeholder] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[unserializable]';
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Check if the given level meets the minimum threshold.
     *
     * @param string $level The log level to check
     * @return bool True if the level should be processed
     */
    private function isHandled(string $level): bool
    {
        return (self::LEVELS[$level] ?? 0) >= (self::LEVELS[$this->minimumLevel] ?? 0);
    }
}
