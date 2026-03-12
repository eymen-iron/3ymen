<?php

declare(strict_types=1);

namespace Eymen\Cache\Drivers;

use Eymen\Cache\CacheInterface;

/**
 * File-based cache driver.
 *
 * Stores cache items as serialized PHP files with TTL support.
 * Uses a two-level directory hashing scheme to avoid filesystem
 * bottlenecks from too many files in a single directory.
 */
final class FileDriver implements CacheInterface
{
    private string $directory;

    private string $prefix;

    /**
     * @param string $directory The base directory for cache files
     * @param string $prefix Key prefix to namespace cache entries
     */
    public function __construct(string $directory, string $prefix = '3ymen_')
    {
        $this->directory = rtrim($directory, '/\\');
        $this->prefix = $prefix;

        $this->ensureDirectory($this->directory);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return $default;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return $default;
        }

        $payload = @unserialize($contents);

        if ($payload === false || !is_array($payload) || !array_key_exists('value', $payload)) {
            $this->deleteFile($path);
            return $default;
        }

        // Check expiry: 0 means no expiration
        if ($payload['expiry'] !== 0 && $payload['expiry'] < time()) {
            $this->deleteFile($path);
            return $default;
        }

        return $payload['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $path = $this->path($key);

        $this->ensureDirectory(dirname($path));

        $expiry = $ttl > 0 ? time() + $ttl : 0;

        $payload = serialize([
            'expiry' => $expiry,
            'value' => $value,
        ]);

        return file_put_contents($path, $payload, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $path = $this->path($key);

        return $this->deleteFile($path);
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    public function flush(): bool
    {
        return $this->removeDirectory($this->directory);
    }

    public function many(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function setMany(array $values, int $ttl = 0): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function increment(string $key, int $value = 1): int|false
    {
        $current = $this->get($key);

        if ($current === null) {
            $newValue = $value;
        } elseif (is_int($current)) {
            $newValue = $current + $value;
        } else {
            return false;
        }

        // Preserve the original TTL by reading the raw payload
        $ttl = $this->getRemainingTtl($key);

        return $this->set($key, $newValue, $ttl) ? $newValue : false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
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
     * Hash a cache key to a file path with two-level directory nesting.
     *
     * For example, a key hashing to "ab3f..." becomes:
     *   $directory/ab/3f/ab3f...
     *
     * This prevents any single directory from accumulating too many entries.
     */
    private function path(string $key): string
    {
        $hash = sha1($this->prefix . $key);

        return $this->directory
            . DIRECTORY_SEPARATOR . substr($hash, 0, 2)
            . DIRECTORY_SEPARATOR . substr($hash, 2, 2)
            . DIRECTORY_SEPARATOR . $hash;
    }

    /**
     * Create a directory if it does not exist.
     */
    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Delete a file if it exists.
     */
    private function deleteFile(string $path): bool
    {
        if (is_file($path)) {
            return @unlink($path);
        }

        return true;
    }

    /**
     * Recursively remove a directory and all its contents, then recreate it.
     */
    private function removeDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        // Recreate the base directory so the driver remains functional
        $this->ensureDirectory($directory);

        return true;
    }

    /**
     * Get the remaining TTL for a cached key in seconds.
     *
     * Returns 0 if the key has no expiry or does not exist.
     */
    private function getRemainingTtl(string $key): int
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return 0;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return 0;
        }

        $payload = @unserialize($contents);

        if (!is_array($payload) || !isset($payload['expiry'])) {
            return 0;
        }

        if ($payload['expiry'] === 0) {
            return 0;
        }

        $remaining = $payload['expiry'] - time();

        return $remaining > 0 ? $remaining : 0;
    }
}
