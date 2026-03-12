<?php

declare(strict_types=1);

namespace Eymen\Container;

/**
 * Lazy service provider registry.
 *
 * Maps interface/abstract types to their service providers. When a deferred
 * service is first resolved from the container, the corresponding provider
 * is booted on demand rather than eagerly during application startup.
 */
final class LazyServiceRegistry
{
    /** @var array<string, string> Interface => Provider class mapping */
    private array $deferredMap = [];

    /** @var array<string, bool> Already-booted provider tracking */
    private array $bootedProviders = [];

    /**
     * Register a deferred service mapping.
     *
     * @param string $interface The interface/abstract type
     * @param string $providerClass The service provider class that provides it
     */
    public function register(string $interface, string $providerClass): void
    {
        $this->deferredMap[$interface] = $providerClass;
    }

    /**
     * Register mappings from a provider's provides() method.
     *
     * @param string $providerClass The service provider class
     * @param list<string> $provides The interfaces this provider supplies
     */
    public function registerProvider(string $providerClass, array $provides): void
    {
        foreach ($provides as $interface) {
            $this->deferredMap[$interface] = $providerClass;
        }
    }

    /**
     * Look up the provider class for a given interface.
     *
     * Returns null if no deferred provider is registered, or if the provider
     * has already been booted.
     */
    public function resolve(string $interface): ?string
    {
        if (!isset($this->deferredMap[$interface])) {
            return null;
        }

        $providerClass = $this->deferredMap[$interface];

        if (isset($this->bootedProviders[$providerClass])) {
            return null;
        }

        return $providerClass;
    }

    /**
     * Mark a provider as booted so it won't be resolved again.
     */
    public function markBooted(string $providerClass): void
    {
        $this->bootedProviders[$providerClass] = true;
    }

    /**
     * Check if a provider has been booted.
     */
    public function isBooted(string $providerClass): bool
    {
        return isset($this->bootedProviders[$providerClass]);
    }

    /**
     * Get all deferred mappings.
     *
     * @return array<string, string>
     */
    public function getDeferredMap(): array
    {
        return $this->deferredMap;
    }

    /**
     * Check if an interface has a deferred provider.
     */
    public function isDeferred(string $interface): bool
    {
        return isset($this->deferredMap[$interface]);
    }

    /**
     * Reset the registry (useful for testing).
     */
    public function reset(): void
    {
        $this->deferredMap = [];
        $this->bootedProviders = [];
    }
}
