<?php

declare(strict_types=1);

namespace Eymen\Auth;

/**
 * Authentication manager.
 *
 * Manages multiple authentication guards and provides a unified
 * interface for authentication operations, delegating to the
 * appropriate guard based on configuration.
 */
final class AuthManager
{
    /** @var array<string, GuardInterface> */
    private array $guards = [];

    /** @var array<string, mixed> */
    private array $config;

    private string $defaultGuard = 'session';

    /**
     * @param array<string, mixed> $config Authentication configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultGuard = $config['default'] ?? 'session';
    }

    /**
     * Get a guard instance by name.
     *
     * @param string|null $name The guard name, or null for the default
     * @return GuardInterface The guard instance
     *
     * @throws \InvalidArgumentException If the guard is not registered
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name ??= $this->defaultGuard;

        if (!isset($this->guards[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Authentication guard [%s] is not registered.', $name)
            );
        }

        return $this->guards[$name];
    }

    /**
     * Register a guard instance.
     *
     * @param string $name The guard name
     * @param GuardInterface $guard The guard instance
     */
    public function addGuard(string $name, GuardInterface $guard): void
    {
        $this->guards[$name] = $guard;
    }

    /**
     * Determine if the current user is authenticated via the default guard.
     */
    public function check(): bool
    {
        return $this->guard()->check();
    }

    /**
     * Determine if the current user is a guest via the default guard.
     */
    public function guest(): bool
    {
        return $this->guard()->guest();
    }

    /**
     * Get the currently authenticated user via the default guard.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $this->guard()->user();
    }

    /**
     * Get the ID of the currently authenticated user via the default guard.
     */
    public function id(): mixed
    {
        return $this->guard()->id();
    }

    /**
     * Attempt to authenticate using the default guard.
     *
     * For SessionGuard, returns bool.
     * For JwtGuard, this method returns bool (use guard() directly for token).
     *
     * @param array<string, mixed> $credentials
     */
    public function attempt(array $credentials): bool
    {
        $guard = $this->guard();

        if ($guard instanceof SessionGuard) {
            return $guard->attempt($credentials);
        }

        if ($guard instanceof JwtGuard) {
            return $guard->attempt($credentials) !== null;
        }

        return $guard->validate($credentials);
    }

    /**
     * Log a user into the application via the default guard.
     *
     * Only supported by SessionGuard.
     *
     * @param array<string, mixed> $user The user record
     *
     * @throws \BadMethodCallException If the guard does not support login
     */
    public function login(array $user): void
    {
        $guard = $this->guard();

        if (!$guard instanceof SessionGuard) {
            throw new \BadMethodCallException(
                sprintf('The [%s] guard does not support the login() method.', $this->defaultGuard)
            );
        }

        $guard->login($user);
    }

    /**
     * Log the user out of the application via the default guard.
     *
     * Only supported by SessionGuard.
     *
     * @throws \BadMethodCallException If the guard does not support logout
     */
    public function logout(): void
    {
        $guard = $this->guard();

        if (!$guard instanceof SessionGuard) {
            throw new \BadMethodCallException(
                sprintf('The [%s] guard does not support the logout() method.', $this->defaultGuard)
            );
        }

        $guard->logout();
    }

    /**
     * Set the default guard name.
     *
     * @param string $name The guard name
     */
    public function setDefaultGuard(string $name): void
    {
        $this->defaultGuard = $name;
    }

    /**
     * Get the default guard name.
     */
    public function getDefaultGuard(): string
    {
        return $this->defaultGuard;
    }

    /**
     * Check if a guard has been registered.
     *
     * @param string $name The guard name
     */
    public function hasGuard(string $name): bool
    {
        return isset($this->guards[$name]);
    }
}
