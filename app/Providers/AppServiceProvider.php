<?php

declare(strict_types=1);

namespace App\Providers;

use Eymen\Container\ServiceProvider;

/**
 * Application service provider.
 *
 * Register and boot application-specific services.
 * This is the default provider included with every new 3ymen project.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application bindings.
     *
     * Use this method to bind interfaces to implementations,
     * register singletons, and define factory bindings.
     */
    public function register(): void
    {
        // Register application bindings
    }

    /**
     * Bootstrap application services.
     *
     * This method is called after all providers have been registered.
     * You can safely resolve services from the container here.
     */
    public function boot(): void
    {
        // Boot application services
    }
}
