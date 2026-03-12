<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * PDO wrapper with multi-driver support (MySQL, PostgreSQL, SQLite).
 *
 * Provides a clean interface for database operations including query execution,
 * transaction management, and query logging. Supports prepared statements with
 * parameter binding for SQL injection prevention.
 */
final class Connection
{
    private \PDO $pdo;
    private string $driver;
    private string $prefix;

    /** @var array<int, array{query: string, bindings: array<mixed>, time: float}> */
    private array $queryLog = [];
    private bool $logging = false;

    /**
     * Create a new database connection.
     *
     * @param array{
     *     driver: string,
     *     host?: string,
     *     port?: int|string,
     *     database?: string,
     *     username?: string,
     *     password?: string,
     *     charset?: string,
     *     collation?: string,
     *     prefix?: string,
     *     schema?: string,
     *     options?: array<int, mixed>
     * } $config Connection configuration
     *
     * @throws \InvalidArgumentException If driver is unsupported
     * @throws \RuntimeException If connection fails
     */
    public function __construct(array $config)
    {
        $this->driver = $config['driver'] ?? 'sqlite';
        $this->prefix = $config['prefix'] ?? '';

        $dsn = match ($this->driver) {
            'mysql' => $this->mysqlDsn($config),
            'pgsql' => $this->pgsqlDsn($config),
            'sqlite' => $this->sqliteDsn($config),
            default => throw new \InvalidArgumentException(
                sprintf('Unsupported database driver: %s. Supported: mysql, pgsql, sqlite.', $this->driver)
            ),
        };

        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        $defaultOptions = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        $options = array_replace($defaultOptions, $config['options'] ?? []);

        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                sprintf('Database connection failed: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e
            );
        }

        // Set charset for MySQL connections via SET NAMES
        if ($this->driver === 'mysql') {
            $charset = $config['charset'] ?? 'utf8mb4';
            $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
            $this->pdo->exec(sprintf("SET NAMES '%s' COLLATE '%s'", $charset, $collation));
        }

        // Set schema search path for PostgreSQL
        if ($this->driver === 'pgsql' && isset($config['schema'])) {
            $this->pdo->exec(sprintf('SET search_path TO %s', $config['schema']));
        }

        // Enable WAL mode for SQLite for better concurrent performance
        if ($this->driver === 'sqlite' && ($config['database'] ?? '') !== ':memory:') {
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA foreign_keys=ON');
        }
    }

    /**
     * Build MySQL DSN string.
     *
     * @param array<string, mixed> $config
     */
    private function mysqlDsn(array $config): string
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        return sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);
    }

    /**
     * Build PostgreSQL DSN string.
     *
     * @param array<string, mixed> $config
     */
    private function pgsqlDsn(array $config): string
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 5432;
        $database = $config['database'] ?? '';

        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    /**
     * Build SQLite DSN string.
     *
     * @param array<string, mixed> $config
     */
    private function sqliteDsn(array $config): string
    {
        $database = $config['database'] ?? ':memory:';

        if ($database === ':memory:') {
            return 'sqlite::memory:';
        }

        return sprintf('sqlite:%s', $database);
    }

    /**
     * Execute a raw SQL query with bindings and return the PDOStatement.
     *
     * @param string $sql SQL query with placeholders
     * @param array<mixed> $bindings Parameter bindings
     * @return \PDOStatement The executed statement
     *
     * @throws \RuntimeException If query execution fails
     */
    public function query(string $sql, array $bindings = []): \PDOStatement
    {
        $start = microtime(true);

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($bindings);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                sprintf('Query failed: %s | SQL: %s', $e->getMessage(), $sql),
                (int) $e->getCode(),
                $e
            );
        }

        if ($this->logging) {
            $this->queryLog[] = [
                'query' => $sql,
                'bindings' => $bindings,
                'time' => microtime(true) - $start,
            ];
        }

        return $statement;
    }

    /**
     * Execute a SELECT query and return all results as associative arrays.
     *
     * @param string $sql SQL SELECT query
     * @param array<mixed> $bindings Parameter bindings
     * @return array<int, array<string, mixed>> Result rows
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute an INSERT query.
     *
     * @param string $sql SQL INSERT query
     * @param array<mixed> $bindings Parameter bindings
     * @return bool Whether the insert succeeded
     */
    public function insert(string $sql, array $bindings = []): bool
    {
        return $this->query($sql, $bindings)->rowCount() > 0;
    }

    /**
     * Execute an UPDATE query and return the number of affected rows.
     *
     * @param string $sql SQL UPDATE query
     * @param array<mixed> $bindings Parameter bindings
     * @return int Number of affected rows
     */
    public function update(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    /**
     * Execute a DELETE query and return the number of affected rows.
     *
     * @param string $sql SQL DELETE query
     * @param array<mixed> $bindings Parameter bindings
     * @return int Number of affected rows
     */
    public function delete(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    /**
     * Execute a general SQL statement (DDL, etc.).
     *
     * @param string $sql SQL statement
     * @param array<mixed> $bindings Parameter bindings
     * @return bool Whether the statement executed successfully
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        $start = microtime(true);

        try {
            $statement = $this->pdo->prepare($sql);
            $result = $statement->execute($bindings);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                sprintf('Statement failed: %s | SQL: %s', $e->getMessage(), $sql),
                (int) $e->getCode(),
                $e
            );
        }

        if ($this->logging) {
            $this->queryLog[] = [
                'query' => $sql,
                'bindings' => $bindings,
                'time' => microtime(true) - $start,
            ];
        }

        return $result;
    }

    /**
     * Get the last inserted row ID.
     *
     * @return string The last insert ID as a string
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a database transaction.
     *
     * @return bool Whether the transaction started successfully
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     *
     * @return bool Whether the commit succeeded
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     *
     * @return bool Whether the rollback succeeded
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Execute a callback within a transaction.
     *
     * Automatically commits on success and rolls back on exception.
     *
     * @param \Closure $callback The callback to execute within the transaction
     * @return mixed The return value of the callback
     *
     * @throws \Throwable Re-throws any exception after rolling back
     */
    public function transaction(\Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Get the underlying PDO instance.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Get the database driver name.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get the table prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Enable query logging.
     */
    public function enableQueryLog(): void
    {
        $this->logging = true;
    }

    /**
     * Disable query logging.
     */
    public function disableQueryLog(): void
    {
        $this->logging = false;
    }

    /**
     * Get the query log.
     *
     * @return array<int, array{query: string, bindings: array<mixed>, time: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Determine if a table exists in the database.
     *
     * @param string $table Table name (without prefix)
     * @return bool Whether the table exists
     */
    public function tableExists(string $table): bool
    {
        $table = $this->prefix . $table;

        return match ($this->driver) {
            'sqlite' => (bool) $this->select(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
                [$table]
            ),
            'mysql' => (bool) $this->select(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = ?',
                [$table]
            ),
            'pgsql' => (bool) $this->select(
                "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename = ?",
                [$table]
            ),
            default => false,
        };
    }
}
