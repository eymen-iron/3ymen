<?php

declare(strict_types=1);

namespace Eymen\Session;

/**
 * Session store contract.
 *
 * Provides a unified interface for session management including
 * attribute access, flash data, CSRF tokens, and session lifecycle.
 */
interface SessionInterface
{
    /**
     * Start the session.
     *
     * @return bool True if the session was started successfully
     */
    public function start(): bool;

    /**
     * Get the session ID.
     */
    public function getId(): string;

    /**
     * Set the session ID.
     *
     * @param string $id Session identifier
     */
    public function setId(string $id): void;

    /**
     * Get the session name.
     */
    public function getName(): string;

    /**
     * Get a value from the session.
     *
     * @param string $key Attribute key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The session value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in the session.
     *
     * @param string $key Attribute key
     * @param mixed $value Value to store
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a key exists in the session.
     *
     * @param string $key Attribute key
     */
    public function has(string $key): bool;

    /**
     * Get all session attributes.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Remove a value from the session.
     *
     * @param string $key Attribute key
     * @return mixed The removed value, or null if not present
     */
    public function remove(string $key): mixed;

    /**
     * Remove all attributes from the session.
     */
    public function clear(): void;

    /**
     * Flash a key-value pair to the session for the next request.
     *
     * @param string $key Flash key
     * @param mixed $value Flash value
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Retrieve a flash value from the session.
     *
     * @param string $key Flash key
     * @param mixed $default Default if not present
     * @return mixed The flash value or default
     */
    public function getFlash(string $key, mixed $default = null): mixed;

    /**
     * Check if a flash key exists.
     *
     * @param string $key Flash key
     */
    public function hasFlash(string $key): bool;

    /**
     * Reflash all flash data for an additional request.
     */
    public function reflash(): void;

    /**
     * Regenerate the session ID.
     *
     * @param bool $destroy Whether to destroy the old session data
     * @return bool True on success
     */
    public function regenerate(bool $destroy = false): bool;

    /**
     * Invalidate the session: clear data and regenerate ID.
     *
     * @return bool True on success
     */
    public function invalidate(): bool;

    /**
     * Check if the session has been started.
     */
    public function isStarted(): bool;

    /**
     * Save the session data to the driver.
     */
    public function save(): void;

    /**
     * Destroy the session entirely.
     */
    public function destroy(): void;

    /**
     * Get or generate a CSRF token.
     *
     * @return string The CSRF token
     */
    public function token(): string;

    /**
     * Get the previous URL from the session.
     */
    public function previousUrl(): ?string;

    /**
     * Set the previous URL in the session.
     *
     * @param string $url The URL to store
     */
    public function setPreviousUrl(string $url): void;
}
