<?php

declare(strict_types=1);

namespace Eymen\Foundation;

use Eymen\Container\Container;
use Eymen\Container\LazyServiceRegistry;
use Eymen\Container\ServiceProvider;
use Eymen\Support\Config;
use Eymen\Support\Env;

/**
 * The main application class.
 *
 * Bootstraps the framework by extending the DI Container. Manages service
 * providers, configuration loading, environment detection, path resolution,
 * and the application lifecycle.
 *
 * Boot lifecycle:
 * 1. Config load (cached if available, else parse)
 * 2. User providers register() (only binding definitions)
 * 3. Middleware pipeline setup
 * 4. Router match route
 * 5. Controller resolve (DI)
 * 6. User providers boot()
 * 7. Response send
 */
class Application extends Container
{
    /** @var static|null Singleton application instance */
    private static ?Application $instance = null;

    /** @var string Base path of the application */
    private string $basePath;

    /** @var bool Whether the application has been booted */
    private bool $booted = false;

    /** @var array<string, ServiceProvider> Registered service providers */
    private array $serviceProviders = [];

    /** @var array<string, bool> Providers that have been booted */
    private array $bootedProviders = [];

    /** @var LazyServiceRegistry Lazy/deferred service registry */
    private LazyServiceRegistry $lazyRegistry;

    /** @var bool Whether env file has been loaded */
    private bool $envLoaded = false;

    /** @var bool Whether config has been loaded */
    private bool $configLoaded = false;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->lazyRegistry = new LazyServiceRegistry();

        static::$instance = $this;

        $this->registerBaseBindings();
        $this->loadEnvironment();
        $this->loadConfig();
        $this->registerUserProviders();
    }

    /**
     * Get the global application instance.
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            throw new \RuntimeException('Application has not been instantiated.');
        }

        return static::$instance;
    }

    /**
     * Set the global application instance.
     */
    public static function setInstance(?Application $app): void
    {
        static::$instance = $app;
    }

    // ========================================================================
    // Path Helpers
    // ========================================================================

    /**
     * Get the base path of the application.
     */
    public function basePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath, $path);
    }

    /**
     * Get the storage path.
     */
    public function storagePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath . '/storage', $path);
    }

    /**
     * Get the configuration path.
     */
    public function configPath(string $path = ''): string
    {
        return $this->joinPath($this->basePath . '/config', $path);
    }

    /**
     * Get the resources path.
     */
    public function resourcePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath . '/resources', $path);
    }

    /**
     * Get the public path.
     */
    public function publicPath(string $path = ''): string
    {
        return $this->joinPath($this->basePath . '/public', $path);
    }

    /**
     * Get the database path.
     */
    public function databasePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath . '/database', $path);
    }

    // ========================================================================
    // Provider Management
    // ========================================================================

    /**
     * Register a service provider.
     *
     * If the provider is already registered, the existing instance is returned.
     * Deferred providers are stored for lazy resolution rather than registered
     * immediately.
     *
     * @param string|ServiceProvider $provider Provider class name or instance
     * @return ServiceProvider The registered provider instance
     */
    public function register(string|ServiceProvider $provider): ServiceProvider
    {
        $providerClass = is_string($provider) ? $provider : get_class($provider);

        // Already registered?
        if (isset($this->serviceProviders[$providerClass])) {
            return $this->serviceProviders[$providerClass];
        }

        // Instantiate if class name provided
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        // Check if provider is deferred
        $provides = $provider->provides();
        if ($provides !== []) {
            $this->lazyRegistry->registerProvider($providerClass, $provides);
        }

        // Call register()
        $provider->register();

        $this->serviceProviders[$providerClass] = $provider;

        // If already booted, boot this provider immediately
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Boot all registered providers.
     *
     * Called once during the request lifecycle. After booting, any newly
     * registered providers will be booted immediately.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;
    }

    /**
     * Check if the application has been booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    // ========================================================================
    // Config Loading
    // ========================================================================

    /**
     * Load configuration files.
     *
     * Tries to load from cache first. If no cache exists, loads individual
     * config files from the config/ directory.
     */
    public function loadConfig(): void
    {
        if ($this->configLoaded) {
            return;
        }

        $config = new Config($this->configPath());

        // Try cached config first
        $cachePath = $this->storagePath('framework/config.cache.php');
        if (is_file($cachePath)) {
            $config->loadFromCache($cachePath);
        }

        $this->instance(Config::class, $config);

        $this->configLoaded = true;
    }

    // ========================================================================
    // Environment
    // ========================================================================

    /**
     * Get the current application environment.
     */
    public function environment(): string
    {
        return (string) ($this->resolveConfig('app.env', 'production'));
    }

    /**
     * Check if running in local/development environment.
     */
    public function isLocal(): bool
    {
        return $this->environment() === 'local';
    }

    /**
     * Check if running in production environment.
     */
    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    /**
     * Check if running in testing environment.
     */
    public function isTesting(): bool
    {
        return $this->environment() === 'testing';
    }

    /**
     * Check if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return (bool) $this->resolveConfig('app.debug', false);
    }

    // ========================================================================
    // Lifecycle
    // ========================================================================

    /**
     * Terminate the application.
     *
     * Called after the response has been sent. Allows providers to perform
     * cleanup operations such as flushing caches or closing connections.
     */
    public function terminate(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'terminate')) {
                $provider->terminate();
            }
        }
    }

    // ========================================================================
    // Container Override — Lazy Resolution
    // ========================================================================

    /**
     * Resolve a type from the container with lazy provider support.
     *
     * Overrides the parent Container::resolve() to add lazy service provider
     * boot-on-demand functionality.
     *
     * @param array<string, mixed> $parameters
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        // Check if a deferred provider can supply this binding
        $providerClass = $this->lazyRegistry->resolve($abstract);

        if ($providerClass !== null) {
            $this->bootDeferredProvider($providerClass);

            // After booting the provider, the binding should be registered
            if (isset($this->bindings[$abstract]) || isset($this->resolved[$abstract])) {
                return parent::resolve($abstract, $parameters);
            }
        }

        return parent::resolve($abstract, $parameters);
    }

    /**
     * Get the lazy service registry.
     */
    public function getLazyRegistry(): LazyServiceRegistry
    {
        return $this->lazyRegistry;
    }

    // ========================================================================
    // Private Methods
    // ========================================================================

    /**
     * Register base bindings that the framework needs to function.
     */
    private function registerBaseBindings(): void
    {
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(LazyServiceRegistry::class, $this->lazyRegistry);
    }

    /**
     * Load environment variables from .env file.
     */
    private function loadEnvironment(): void
    {
        if ($this->envLoaded) {
            return;
        }

        $envFile = $this->basePath . '/.env';

        if (is_file($envFile)) {
            Env::load($envFile);
        }

        $this->envLoaded = true;
    }

    /**
     * Register user-defined service providers from bootstrap/providers.php.
     */
    private function registerUserProviders(): void
    {
        $providersFile = $this->basePath . '/bootstrap/providers.php';

        if (!is_file($providersFile)) {
            return;
        }

        $providers = require $providersFile;

        if (!is_array($providers)) {
            return;
        }

        foreach ($providers as $providerClass) {
            if (is_string($providerClass) && class_exists($providerClass)) {
                $this->register($providerClass);
            }
        }
    }

    /**
     * Boot an individual service provider.
     */
    private function bootProvider(ServiceProvider $provider): void
    {
        $class = get_class($provider);

        if (isset($this->bootedProviders[$class])) {
            return;
        }

        $provider->boot();

        $this->bootedProviders[$class] = true;
    }

    /**
     * Boot a deferred provider by class name.
     */
    private function bootDeferredProvider(string $providerClass): void
    {
        if ($this->lazyRegistry->isBooted($providerClass)) {
            return;
        }

        // Register and boot the provider if not already done
        if (!isset($this->serviceProviders[$providerClass])) {
            if (class_exists($providerClass)) {
                $this->register($providerClass);
            }
        }

        $provider = $this->serviceProviders[$providerClass] ?? null;

        if ($provider !== null) {
            $this->bootProvider($provider);
        }

        $this->lazyRegistry->markBooted($providerClass);
    }

    /**
     * Join a base path with a sub-path.
     */
    private function joinPath(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/\\');
    }

    /**
     * Safely resolve a config value without infinite recursion.
     */
    private function resolveConfig(string $key, mixed $default): mixed
    {
        if (!$this->configLoaded) {
            return $default;
        }

        try {
            if (isset($this->resolved[Config::class])) {
                /** @var Config $config */
                $config = $this->resolved[Config::class];
                return $config->get($key, $default);
            }
        } catch (\Throwable) {
            // Fall through to default
        }

        return $default;
    }
}
