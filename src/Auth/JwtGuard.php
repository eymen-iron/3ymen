<?php

declare(strict_types=1);

namespace Eymen\Auth;

use Eymen\Database\Connection;
use Eymen\Http\Psr\ServerRequestInterface;

/**
 * JWT-based authentication guard.
 *
 * Authenticates users via JSON Web Tokens, validating the token
 * from the Authorization: Bearer header and resolving user records
 * from the database.
 */
final class JwtGuard implements GuardInterface
{
    /** @var array<string, mixed>|null */
    private ?array $user = null;
    private JwtEncoder $jwt;
    private Connection $connection;
    private string $secret;
    private string $table;
    private string $algo;
    private int $ttl;
    private ?string $currentToken = null;
    private bool $loaded = false;

    /**
     * @param JwtEncoder $jwt The JWT encoder/decoder
     * @param Connection $connection The database connection
     * @param array<string, mixed> $config Guard configuration
     */
    public function __construct(JwtEncoder $jwt, Connection $connection, array $config = [])
    {
        $this->jwt = $jwt;
        $this->connection = $connection;
        $this->secret = $config['secret'] ?? '';
        $this->table = $config['table'] ?? 'users';
        $this->algo = $config['algo'] ?? 'HS256';
        $this->ttl = $config['ttl'] ?? 3600;
    }

    /**
     * Attempt to authenticate using credentials and return a JWT token.
     *
     * @param array<string, mixed> $credentials Must contain identifier field and 'password'
     * @return string|null The JWT token on success, null on failure
     */
    public function attempt(array $credentials): ?string
    {
        $password = $credentials['password'] ?? '';
        unset($credentials['password']);

        $user = $this->findByCredentials($credentials);

        if ($user === null) {
            return null;
        }

        if (!password_verify((string) $password, (string) ($user['password'] ?? ''))) {
            return null;
        }

        $this->user = $user;
        $this->loaded = true;

        $payload = [
            'sub' => $user['id'],
            'iat' => time(),
            'exp' => time() + $this->ttl,
        ];

        $token = $this->jwt->encode($payload, $this->secret, $this->algo);
        $this->currentToken = $token;

        return $token;
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

        if ($this->currentToken === null) {
            return null;
        }

        $payload = $this->parseToken($this->currentToken);

        if ($payload === null) {
            return null;
        }

        $userId = $payload['sub'] ?? null;

        if ($userId === null) {
            return null;
        }

        $users = $this->connection->select(
            sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $this->table),
            [$userId]
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
     * Validate a user's credentials without generating a token.
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
     * Parse and verify a JWT token, returning the payload.
     *
     * @param string $token The JWT token to parse
     * @return array<string, mixed>|null The payload, or null if invalid/expired
     */
    public function parseToken(string $token): ?array
    {
        try {
            return $this->jwt->decode($token, $this->secret, $this->algo);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Set the current token from an incoming request's Authorization header.
     *
     * Extracts the Bearer token from the Authorization header.
     *
     * @param ServerRequestInterface $request The incoming request
     */
    public function setTokenFromRequest(ServerRequestInterface $request): void
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || !str_starts_with($header, 'Bearer ')) {
            $this->currentToken = null;
            $this->user = null;
            $this->loaded = false;
            return;
        }

        $token = substr($header, 7);
        $this->currentToken = $token;
        $this->user = null;
        $this->loaded = false;
    }

    /**
     * Get the current JWT token.
     */
    public function getToken(): ?string
    {
        return $this->currentToken;
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
