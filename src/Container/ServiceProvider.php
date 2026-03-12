<?php

declare(strict_types=1);

namespace Eymen\Container;

use Eymen\Foundation\Application;

/**
 * Base service provider class.
 *
 * Service providers are the central place to configure and bootstrap application
 * services. The register() method defines bindings; boot() runs after all
 * providers are registered and can depend on other services.
 */
abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register bindings in the container.
     *
     * This method should only define bindings. Do not attempt to resolve
     * or use other services here, as they may not be registered yet.
     */
    abstract public function register(): void;

    /**
     * Bootstrap application services.
     *
     * Called after all providers have been registered. You can safely
     * resolve services from the container here.
     */
    public function boot(): void
    {
        // Default: no boot logic
    }

    /**
     * Get the services provided by this provider.
     *
     * If this returns a non-empty array, the provider is treated as deferred
     * and will only be registered/booted when one of these services is requested.
     *
     * @return list<string>
     */
    public function provides(): array
    {
        return [];
    }
}
