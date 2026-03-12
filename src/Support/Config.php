<?php

declare(strict_types=1);

namespace Eymen\Support;

/**
 * Configuration repository with dot-notation access.
 *
 * Loads PHP configuration files from a directory and provides
 * convenient dot-notation access (e.g., 'app.name', 'database.default').
 */
final class Config
{
    /** @var array<string, mixed> The configuration items */
    private array $items = [];

    /** @var string|null The configuration directory path */
    private ?string $directory = null;

    /** @var array<string> Already loaded config files */
    private array $loadedFiles = [];

    /**
     * Create a new configuration repository.
     *
     * @param string|null $directory Path to configuration directory
     * @param array<string, mixed> $items Pre-loaded configuration items
     */
    public function __construct(?string $directory = null, array $items = [])
    {
        $this->directory = $directory;
        $this->items = $items;
    }

    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key Dot-notated key (e.g., 'app.name')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureFileLoaded($key);

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Set a configuration value using dot notation.
     *
     * @param string $key Dot-notated key
     * @param mixed $value The value to set
     */
    public function set(string $key, mixed $value): void
    {
        Arr::set($this->items, $key, $value);
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key Dot-notated key
     */
    public function has(string $key): bool
    {
        $this->ensureFileLoaded($key);

        return Arr::has($this->items, $key);
    }

    /**
     * Get all configuration items.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->loadAllFiles();

        return $this->items;
    }

    /**
     * Load configuration from a cached array file.
     *
     * The file should return a PHP array containing all merged configuration.
     *
     * @param string $path Path to the cached configuration file
     * @throws \RuntimeException If the cache file is invalid
     */
    public function loadFromCache(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Config cache file not found: {$path}");
        }

        $cached = require $path;

        if (!is_array($cached)) {
            throw new \RuntimeException("Config cache file must return an array: {$path}");
        }

        $this->items = $cached;
        $this->directory = null; // Disable lazy loading when using cache
    }

    /**
     * Cache all configuration to a single PHP file.
     *
     * @param string $path Path where the cache file will be written
     * @throws \RuntimeException If the cache file cannot be written
     */
    public function cache(string $path): void
    {
        $this->loadAllFiles();

        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create cache directory: {$dir}");
        }

        $export = var_export($this->items, true);
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$export};\n";

        $result = file_put_contents($path, $content, LOCK_EX);

        if ($result === false) {
            throw new \RuntimeException("Failed to write config cache: {$path}");
        }
    }

    /**
     * Forget a configuration key.
     *
     * @param string $key Dot-notated key to remove
     */
    public function forget(string $key): void
    {
        Arr::forget($this->items, $key);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param string $key Dot-notated key
     * @param mixed $value Value to push
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        if (!is_array($array)) {
            $array = [$array];
        }

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Merge configuration values.
     *
     * @param array<string, mixed> $items Items to merge
     */
    public function merge(array $items): void
    {
        $this->items = array_replace_recursive($this->items, $items);
    }

    /**
     * Ensure the config file for the top-level key is loaded.
     */
    private function ensureFileLoaded(string $key): void
    {
        if ($this->directory === null) {
            return;
        }

        $topKey = explode('.', $key, 2)[0];

        if (in_array($topKey, $this->loadedFiles, true)) {
            return;
        }

        $this->loadFile($topKey);
    }

    /**
     * Load a single configuration file by its top-level key.
     */
    private function loadFile(string $name): void
    {
        if ($this->directory === null) {
            return;
        }

        $path = rtrim($this->directory, '/\\') . '/' . $name . '.php';

        $this->loadedFiles[] = $name;

        if (!is_file($path)) {
            return;
        }

        $config = require $path;

        if (is_array($config)) {
            $this->items[$name] = $config;
        }
    }

    /**
     * Load all configuration files from the directory.
     */
    private function loadAllFiles(): void
    {
        if ($this->directory === null) {
            return;
        }

        $pattern = rtrim($this->directory, '/\\') . '/*.php';

        foreach (glob($pattern) ?: [] as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $this->loadedFiles, true)) {
                continue;
            }

            $this->loadFile($name);
        }
    }
}
