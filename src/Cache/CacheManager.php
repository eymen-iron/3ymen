<?php

declare(strict_types=1);

namespace Eymen\Cache;

use Eymen\Cache\Drivers\ApcuDriver;
use Eymen\Cache\Drivers\FileDriver;

/**
 * Cache manager with automatic driver detection.
 *
 * Resolves the best available cache driver based on configuration or
 * environment capabilities. Prefers APCu when available, falls back to
 * file-based caching.
 *
 * Configuration keys:
 *   - driver: 'auto' | 'apcu' | 'file' (default: 'auto')
 *   - prefix: Key prefix string (default: '3ymen_')
 *   - path:   Directory for file driver (required when driver is 'file' or 'auto' fallback)
 *   - ttl:    Default time-to-live in seconds (default: 3600)
 */
final class CacheManager implements CacheInterface
{
    private CacheInterface $driver;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config Cache configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => 'auto',
            'prefix' => '3ymen_',
            'path' => sys_get_temp_dir() . '/3ymen_cache',
            'ttl' => 3600,
        ], $config);

        $this->driver = $this->resolveDriver();
    }

    /**
     * Resolve the cache driver based on configuration.
     *
     * Resolution order:
     * 1. Explicit driver in config ('apcu' or 'file')
     * 2. Auto-detect: APCu if available, otherwise file
     */
    private function resolveDriver(): CacheInterface
    {
        $driver = $this->config['driver'];
        $prefix = $this->config['prefix'];

        if ($driver === 'apcu') {
            return new ApcuDriver($prefix);
        }

        if ($driver === 'file') {
            return new FileDriver($this->config['path'], $prefix);
        }

        // Auto-detection
        if ($this->isApcuAvailable()) {
            return new ApcuDriver($prefix);
        }

        return new FileDriver($this->config['path'], $prefix);
    }

    /**
     * Check if APCu is available and enabled.
     */
    private function isApcuAvailable(): bool
    {
        return extension_loaded('apcu')
            && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the underlying cache driver instance.
     */
    public function getDriver(): CacheInterface
    {
        return $this->driver;
    }

    /**
     * Get the name of the active driver.
     *
     * @return string 'apcu' or 'file'
     */
    public function getDriverName(): string
    {
        return match (true) {
            $this->driver instanceof ApcuDriver => 'apcu',
            $this->driver instanceof FileDriver => 'file',
            default => 'unknown',
        };
    }

    // -------------------------------------------------------------------------
    // CacheInterface delegation
    // -------------------------------------------------------------------------

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver->get($key, $default);
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return $this->driver->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->driver->delete($key);
    }

    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }

    public function flush(): bool
    {
        return $this->driver->flush();
    }

    public function many(array $keys): array
    {
        return $this->driver->many($keys);
    }

    public function setMany(array $values, int $ttl = 0): bool
    {
        return $this->driver->setMany($values, $ttl);
    }

    public function increment(string $key, int $value = 1): int|false
    {
        return $this->driver->increment($key, $value);
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->driver->decrement($key, $value);
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        return $this->driver->remember($key, $ttl, $callback);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->driver->forever($key, $value);
    }

    public function forget(string $key): bool
    {
        return $this->driver->forget($key);
    }
}
