<?php

declare(strict_types=1);

namespace Eymen\Session;

use Eymen\Session\Drivers\ApcuDriver;
use Eymen\Session\Drivers\FileDriver;

/**
 * Session manager with automatic driver detection.
 *
 * Implements the full SessionInterface contract including flash data,
 * CSRF token generation, session regeneration, and URL tracking.
 * Resolves the best available session driver based on configuration.
 *
 * Configuration keys:
 *   - driver:          'auto' | 'apcu' | 'file' (default: 'auto')
 *   - name:            Session cookie name (default: '3ymen_session')
 *   - lifetime:        Session lifetime in minutes (default: 120)
 *   - path:            Directory for file driver (default: sys_get_temp_dir())
 *   - cookie_path:     Cookie path (default: '/')
 *   - cookie_domain:   Cookie domain (default: null)
 *   - cookie_secure:   Cookie secure flag (default: false)
 *   - cookie_httponly:  Cookie httponly flag (default: true)
 *   - cookie_samesite: Cookie SameSite attribute (default: 'Lax')
 */
final class SessionManager implements SessionInterface
{
    private string $id = '';

    private string $name;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * Flash data tracking.
     *
     * 'new' contains keys flashed during the current request (survive to next request).
     * 'old' contains keys from the previous request (purged on save).
     *
     * @var array{new: list<string>, old: list<string>}
     */
    private array $flash = [
        'new' => [],
        'old' => [],
    ];

    private bool $started = false;

    private SessionDriverInterface $driver;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config Session configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => 'auto',
            'name' => '3ymen_session',
            'lifetime' => 120,
            'path' => sys_get_temp_dir() . '/3ymen_sessions',
            'cookie_path' => '/',
            'cookie_domain' => null,
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ], $config);

        $this->name = $this->config['name'];
        $this->driver = $this->resolveDriver();
    }

    /**
     * Resolve the session storage driver based on configuration.
     */
    private function resolveDriver(): SessionDriverInterface
    {
        $driver = $this->config['driver'];

        if ($driver === 'apcu') {
            return new ApcuDriver();
        }

        if ($driver === 'file') {
            return new FileDriver($this->config['path']);
        }

        // Auto-detection: prefer APCu, fall back to file
        if ($this->isApcuAvailable()) {
            return new ApcuDriver();
        }

        return new FileDriver($this->config['path']);
    }

    /**
     * Check if APCu is available and enabled.
     */
    private function isApcuAvailable(): bool
    {
        return extension_loaded('apcu')
            && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    // -------------------------------------------------------------------------
    // SessionInterface implementation
    // -------------------------------------------------------------------------

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if ($this->id === '') {
            $this->id = $this->generateId();
        }

        $data = $this->driver->read($this->id);

        // Restore attributes
        $this->attributes = $data['_attributes'] ?? [];

        // Restore flash tracking: previous request's 'new' becomes this request's 'old'
        $this->flash = [
            'new' => [],
            'old' => $data['_flash']['new'] ?? [],
        ];

        // Load flash values into attributes so they are accessible this request
        $flashValues = $data['_flash_data'] ?? [];

        foreach ($flashValues as $key => $value) {
            if (!array_key_exists($key, $this->attributes)) {
                $this->attributes[$key] = $value;
            }
        }

        $this->started = true;

        return true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function all(): array
    {
        return $this->attributes;
    }

    public function remove(string $key): mixed
    {
        $value = $this->attributes[$key] ?? null;

        unset($this->attributes[$key]);

        return $value;
    }

    public function clear(): void
    {
        $this->attributes = [];
        $this->flash = ['new' => [], 'old' => []];
    }

    public function flash(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;

        $this->flash['new'][] = $key;

        // Remove from old if present (re-flashing extends its life)
        $this->flash['old'] = array_values(
            array_diff($this->flash['old'], [$key])
        );
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (in_array($key, $this->flash['old'], true) || in_array($key, $this->flash['new'], true)) {
            return $this->attributes[$key] ?? $default;
        }

        return $default;
    }

    public function hasFlash(string $key): bool
    {
        return (in_array($key, $this->flash['old'], true) || in_array($key, $this->flash['new'], true))
            && array_key_exists($key, $this->attributes);
    }

    public function reflash(): void
    {
        $this->flash['new'] = array_values(
            array_unique(array_merge($this->flash['new'], $this->flash['old']))
        );

        $this->flash['old'] = [];
    }

    public function regenerate(bool $destroy = false): bool
    {
        if ($destroy) {
            $this->driver->destroy($this->id);
        }

        $this->id = $this->generateId();

        return true;
    }

    public function invalidate(): bool
    {
        $this->clear();

        return $this->regenerate(true);
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function save(): void
    {
        // Age flash data: purge expired flash keys, rotate tracking
        $this->ageFlashData();

        // Collect flash values that should survive to the next request
        $flashData = [];

        foreach ($this->flash['new'] as $key) {
            if (array_key_exists($key, $this->attributes)) {
                $flashData[$key] = $this->attributes[$key];
            }
        }

        $data = [
            '_attributes' => $this->attributes,
            '_flash' => $this->flash,
            '_flash_data' => $flashData,
        ];

        $this->driver->write($this->id, $data, (int) $this->config['lifetime']);

        $this->started = false;
    }

    public function destroy(): void
    {
        $this->driver->destroy($this->id);
        $this->attributes = [];
        $this->flash = ['new' => [], 'old' => []];
        $this->id = '';
        $this->started = false;
    }

    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->set('_token', $this->generateToken());
        }

        return (string) $this->get('_token');
    }

    public function previousUrl(): ?string
    {
        $url = $this->get('_previous_url');

        return is_string($url) ? $url : null;
    }

    public function setPreviousUrl(string $url): void
    {
        $this->set('_previous_url', $url);
    }

    // -------------------------------------------------------------------------
    // Additional public methods
    // -------------------------------------------------------------------------

    /**
     * Get the underlying session storage driver.
     */
    public function getDriver(): SessionDriverInterface
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

    /**
     * Get cookie parameters for Set-Cookie header generation.
     *
     * @return array<string, mixed>
     */
    public function getCookieParams(): array
    {
        return [
            'lifetime' => (int) $this->config['lifetime'] * 60,
            'path' => $this->config['cookie_path'],
            'domain' => $this->config['cookie_domain'],
            'secure' => (bool) $this->config['cookie_secure'],
            'httponly' => (bool) $this->config['cookie_httponly'],
            'samesite' => $this->config['cookie_samesite'],
        ];
    }

    /**
     * Run garbage collection on the session driver.
     */
    public function gc(): bool
    {
        return $this->driver->gc((int) $this->config['lifetime'] * 60);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Age flash data by purging expired keys and rotating tracking arrays.
     *
     * Called during save() so that flash values survive exactly one more request.
     */
    private function ageFlashData(): void
    {
        // Remove attributes for keys that were in 'old' (they have now expired)
        foreach ($this->flash['old'] as $key) {
            unset($this->attributes[$key]);
        }

        // Current 'new' becomes 'old' for the next request
        $this->flash['old'] = $this->flash['new'];
        $this->flash['new'] = [];
    }

    /**
     * Generate a cryptographically secure session ID.
     *
     * @return string 40-character hex string
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(20));
    }

    /**
     * Generate a cryptographically secure CSRF token.
     *
     * @return string 40-character hex string
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(20));
    }
}
