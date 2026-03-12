<?php

declare(strict_types=1);

namespace Eymen\Container;

/**
 * Fluent builder for contextual bindings.
 *
 * Usage:
 *   $container->when(PhotoController::class)
 *             ->needs(StorageInterface::class)
 *             ->give(LocalStorage::class);
 */
final class ContextualBindingBuilder
{
    private string $needs = '';

    public function __construct(
        private readonly Container $container,
        private readonly string $concrete,
    ) {
    }

    /**
     * Define the abstract type this contextual binding is for.
     */
    public function needs(string $abstract): static
    {
        $this->needs = $abstract;
        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     */
    public function give(string|callable $implementation): void
    {
        $this->container->addContextualBinding($this->concrete, $this->needs, $implementation);
    }
}
