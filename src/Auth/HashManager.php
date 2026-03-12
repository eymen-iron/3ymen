<?php

declare(strict_types=1);

namespace Eymen\Auth;

/**
 * Password hashing manager.
 *
 * Provides a clean interface around PHP's password_hash, password_verify,
 * and password_needs_rehash functions with configurable algorithms and options.
 */
final class HashManager
{
    private string $algo;

    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param string $algo Hashing algorithm (PASSWORD_BCRYPT, PASSWORD_ARGON2ID, etc.)
     * @param array<string, mixed> $options Algorithm-specific options (cost, memory_cost, etc.)
     */
    public function __construct(string $algo = PASSWORD_BCRYPT, array $options = [])
    {
        $this->algo = $algo;
        $this->options = $options;
    }

    /**
     * Hash the given value.
     *
     * @param string $value The plain text value to hash
     * @return string The hashed value
     *
     * @throws \RuntimeException If hashing fails
     */
    public function make(string $value): string
    {
        $hash = password_hash($value, $this->algo, $this->options);

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash value.');
        }

        return $hash;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value The plain text value
     * @param string $hashedValue The hashed value to check against
     * @return bool Whether the value matches the hash
     */
    public function check(string $value, string $hashedValue): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue The hashed value to check
     * @return bool Whether the hash needs to be rehashed
     */
    public function needsRehash(string $hashedValue): bool
    {
        return password_needs_rehash($hashedValue, $this->algo, $this->options);
    }
}
