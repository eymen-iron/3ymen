<?php

declare(strict_types=1);

namespace Eymen\Cache;

/**
 * Cache store contract.
 *
 * Provides a unified interface for cache operations including
 * get/set, bulk operations, atomic increments, and convenience methods.
 */
interface CacheInterface
{
    /**
     * Retrieve an item from the cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int $ttl Time-to-live in seconds (0 = no expiry)
     * @return bool True on success
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * Remove an item from the cache.
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public function delete(string $key): bool;

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key Cache key
     * @return bool True if the key exists and has not expired
     */
    public function has(string $key): bool;

    /**
     * Remove all items from the cache.
     *
     * @return bool True on success
     */
    public function flush(): bool;

    /**
     * Retrieve multiple items from the cache.
     *
     * @param array<int, string> $keys List of cache keys
     * @return array<string, mixed> Associative array of key => value pairs
     */
    public function many(array $keys): array;

    /**
     * Store multiple items in the cache.
     *
     * @param array<string, mixed> $values Associative array of key => value pairs
     * @param int $ttl Time-to-live in seconds (0 = no expiry)
     * @return bool True if all items were stored successfully
     */
    public function setMany(array $values, int $ttl = 0): bool;

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key Cache key
     * @param int $value Amount to increment by
     * @return int|false The new value, or false on failure
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key Cache key
     * @param int $value Amount to decrement by
     * @return int|false The new value, or false on failure
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Get an item from the cache, or execute the given closure and store the result.
     *
     * @param string $key Cache key
     * @param int $ttl Time-to-live in seconds
     * @param \Closure $callback Closure to compute value if not cached
     * @return mixed The cached or computed value
     */
    public function remember(string $key, int $ttl, \Closure $callback): mixed;

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @return bool True on success
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Remove an item from the cache. Alias for delete().
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public function forget(string $key): bool;
}
