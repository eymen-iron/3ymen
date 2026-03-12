<?php

declare(strict_types=1);

namespace Eymen\Auth;

use Eymen\Database\Connection;
use Eymen\Session\SessionInterface;

/**
 * Session-based authentication guard.
 *
 * Authenticates users via session storage, using the database to
 * look up user records and password_verify for credential validation.
 */
final class SessionGuard implements GuardInterface
{
    /** @var array<string, mixed>|null */
    private ?array $user = null;
    private SessionInterface $session;
    private Connection $connection;
    private string $table;
    private string $identifierColumn;
    private bool $loaded = false;

    private const SESSION_KEY = '_auth_user_id';

    /**
     * @param SessionInterface $session The session driver
     * @param Connection $connection The database connection
     * @param array<string, mixed> $config Guard configuration
     */
    public function __construct(SessionInterface $session, Connection $connection, array $config = [])
    {
        $this->session = $session;
        $this->connection = $connection;
        $this->table = $config['table'] ?? 'users';
        $this->identifierColumn = $config['identifier'] ?? 'email';
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials Must contain identifier field and 'password'
     * @return bool Whether authentication succeeded
     */
    public function attempt(array $credentials): bool
    {
        $password = $credentials['password'] ?? '';
        unset($credentials['password']);

        $user = $this->findByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        $hashedPassword = $user['password'] ?? '';

        if (!password_verify((string) $password, (string) $hashedPassword)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    /**
     * Log a user into the application.
     *
     * @param array<string, mixed> $user The user record
     */
    public function login(array $user): void
    {
        $id = $user['id'] ?? null;

        if ($id === null) {
            throw new \InvalidArgumentException('User array must contain an "id" field.');
        }

        $this->session->set(self::SESSION_KEY, $id);
        $this->session->regenerate(false);
        $this->user = $user;
        $this->loaded = true;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(): void
    {
        $this->session->remove(self::SESSION_KEY);
        $this->session->regenerate(true);
        $this->user = null;
        $this->loaded = true;
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }

        $this->loaded = true;
        $id = $this->session->get(self::SESSION_KEY);

        if ($id === null) {
            return null;
        }

        $users = $this->connection->select(
            sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $this->table),
            [$id]
        );

        if ($users === []) {
            return null;
        }

        $this->user = $users[0];

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): mixed
    {
        $user = $this->user();

        return $user['id'] ?? null;
    }

    /**
     * Validate a user's credentials without logging them in.
     *
     * @param array<string, mixed> $credentials
     */
    public function validate(array $credentials): bool
    {
        $password = $credentials['password'] ?? '';
        unset($credentials['password']);

        $user = $this->findByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        return password_verify((string) $password, (string) ($user['password'] ?? ''));
    }

    /**
     * Find a user by non-password credentials.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>|null
     */
    private function findByCredentials(array $credentials): ?array
    {
        if ($credentials === []) {
            return null;
        }

        $conditions = [];
        $bindings = [];

        foreach ($credentials as $column => $value) {
            $conditions[] = sprintf('%s = ?', $column);
            $bindings[] = $value;
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s LIMIT 1',
            $this->table,
            implode(' AND ', $conditions)
        );

        $users = $this->connection->select($sql, $bindings);

        if ($users === []) {
            return null;
        }

        return $users[0];
    }
}
