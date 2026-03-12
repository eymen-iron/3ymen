<?php

declare(strict_types=1);

namespace Eymen\Session;

/**
 * Session storage driver contract.
 *
 * Defines the low-level storage operations that each session
 * backend must implement (file, APCu, etc.).
 */
interface SessionDriverInterface
{
    /**
     * Read session data by ID.
     *
     * @param string $id Session identifier
     * @return array<string, mixed> The stored session data, or empty array if not found
     */
    public function read(string $id): array;

    /**
     * Write session data.
     *
     * @param string $id Session identifier
     * @param array<string, mixed> $data Session data to store
     * @param int $lifetime Session lifetime in minutes
     * @return bool True on success
     */
    public function write(string $id, array $data, int $lifetime): bool;

    /**
     * Destroy a session by ID.
     *
     * @param string $id Session identifier
     * @return bool True on success
     */
    public function destroy(string $id): bool;

    /**
     * Garbage-collect expired sessions.
     *
     * @param int $maxLifetime Maximum session lifetime in seconds
     * @return bool True on success
     */
    public function gc(int $maxLifetime): bool;
}
