<?php

declare(strict_types=1);

namespace Eymen\Cache\Drivers;

use Eymen\Cache\CacheInterface;

/**
 * APCu-based cache driver.
 *
 * Uses the APCu extension for high-performance in-memory caching.
 * APCu stores data in shared memory, making it very fast but limited
 * to the current server and process group.
 */
final class ApcuDriver implements CacheInterface
{
    private string $prefix;

    /**
     * @param string $prefix Key prefix to namespace cache entries
     * @throws \RuntimeException If the APCu extension is not loaded or enabled
     */
    public function __construct(string $prefix = '3ymen_')
    {
        if (!extension_loaded('apcu') || !ini_get('apc.enabled')) {
            throw new \RuntimeException('APCu extension is not loaded or not enabled.');
        }

        $this->prefix = $prefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($this->prefixedKey($key), $success);

        if (!$success) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return apcu_store($this->prefixedKey($key), $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return apcu_delete($this->prefixedKey($key)) !== false;
    }

    public function has(string $key): bool
    {
        return apcu_exists($this->prefixedKey($key));
    }

    public function flush(): bool
    {
        // Only delete keys with our prefix, not the entire APCu store
        if (class_exists(\APCUIterator::class)) {
            $pattern = '/^' . preg_quote($this->prefix, '/') . '/';
            $iterator = new \APCUIterator($pattern);

            foreach ($iterator as $entry) {
                apcu_delete($entry['key']);
            }

            return true;
        }

        // Fallback: clear the entire user cache if APCUIterator is unavailable
        return apcu_clear_cache();
    }

    public function many(array $keys): array
    {
        $prefixedKeys = [];

        foreach ($keys as $key) {
            $prefixedKeys[$this->prefixedKey($key)] = $key;
        }

        $fetched = apcu_fetch(array_keys($prefixedKeys), $success);

        $result = [];

        // Initialize all keys with null
        foreach ($keys as $key) {
            $result[$key] = null;
        }

        if ($success && is_array($fetched)) {
            foreach ($fetched as $prefixedKey => $value) {
                if (isset($prefixedKeys[$prefixedKey])) {
                    $result[$prefixedKeys[$prefixedKey]] = $value;
                }
            }
        }

        return $result;
    }

    public function setMany(array $values, int $ttl = 0): bool
    {
        $prefixed = [];

        foreach ($values as $key => $value) {
            $prefixed[$this->prefixedKey($key)] = $value;
        }

        $errors = apcu_store($prefixed, null, $ttl);

        return empty($errors);
    }

    public function increment(string $key, int $value = 1): int|false
    {
        $prefixed = $this->prefixedKey($key);

        // If the key does not exist, initialize it
        if (!apcu_exists($prefixed)) {
            return apcu_store($prefixed, $value) ? $value : false;
        }

        $result = apcu_inc($prefixed, $value, $success);

        return $success ? (int) $result : false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        $prefixed = $this->prefixedKey($key);

        // If the key does not exist, initialize it
        if (!apcu_exists($prefixed)) {
            $initial = -$value;
            return apcu_store($prefixed, $initial) ? $initial : false;
        }

        $result = apcu_dec($prefixed, $value, $success);

        return $success ? (int) $result : false;
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        $value = $this->get($key, $this);

        if ($value !== $this) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->set($key, $value, 0);
    }

    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    /**
     * Add the prefix to a cache key.
     */
    private function prefixedKey(string $key): string
    {
        return $this->prefix . $key;
    }
}
