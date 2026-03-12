<?php

declare(strict_types=1);

namespace Eymen\Auth;

/**
 * Authentication guard contract.
 *
 * Defines the interface for authentication guards that determine
 * how users are authenticated for each request.
 */
interface GuardInterface
{
    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool;

    /**
     * Determine if the current user is a guest (not authenticated).
     */
    public function guest(): bool;

    /**
     * Get the currently authenticated user.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array;

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): mixed;

    /**
     * Validate a user's credentials without logging them in.
     *
     * @param array<string, mixed> $credentials
     */
    public function validate(array $credentials): bool;
}
