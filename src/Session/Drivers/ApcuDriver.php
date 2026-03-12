<?php

declare(strict_types=1);

namespace Eymen\Session\Drivers;

use Eymen\Session\SessionDriverInterface;

/**
 * APCu-based session storage driver.
 *
 * Stores session data in APCu shared memory with TTL-based expiration.
 * APCu handles garbage collection automatically through its built-in TTL mechanism.
 */
final class ApcuDriver implements SessionDriverInterface
{
    private string $prefix;

    /**
     * @param string $prefix Key prefix to namespace session entries
     * @throws \RuntimeException If APCu is not available
     */
    public function __construct(string $prefix = '3ymen_session_')
    {
        if (!extension_loaded('apcu') || !ini_get('apc.enabled')) {
            throw new \RuntimeException('APCu extension is not loaded or not enabled.');
        }

        $this->prefix = $prefix;
    }

    public function read(string $id): array
    {
        $data = apcu_fetch($this->prefix . $id, $success);

        if (!$success || !is_array($data)) {
            return [];
        }

        return $data;
    }

    public function write(string $id, array $data, int $lifetime): bool
    {
        // Lifetime is in minutes, APCu TTL is in seconds
        $ttl = $lifetime * 60;

        return apcu_store($this->prefix . $id, $data, $ttl);
    }

    public function destroy(string $id): bool
    {
        return apcu_delete($this->prefix . $id) !== false;
    }

    public function gc(int $maxLifetime): bool
    {
        // APCu handles TTL-based expiration automatically.
        // No manual garbage collection needed.
        return true;
    }
}
