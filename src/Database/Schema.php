<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Schema builder for database table management.
 *
 * Provides methods for creating, altering, dropping, and inspecting
 * database tables. Delegates column/index definitions to Blueprint.
 */
final class Schema
{
    private Connection $connection;

    /**
     * @param Connection $connection The database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create a new table.
     *
     * @param string $table Table name (without prefix)
     * @param \Closure $callback Receives a Blueprint instance for defining columns
     */
    public function create(string $table, \Closure $callback): void
    {
        $prefixedTable = $this->connection->getPrefix() . $table;
        $blueprint = new Blueprint($prefixedTable);

        $callback($blueprint);

        $statements = $blueprint->toSql($this->connection->getDriver());

        foreach ($statements as $sql) {
            $this->connection->statement($sql);
        }
    }

    /**
     * Alter an existing table.
     *
     * @param string $table Table name (without prefix)
     * @param \Closure $callback Receives a Blueprint instance for defining changes
     */
    public function table(string $table, \Closure $callback): void
    {
        $prefixedTable = $this->connection->getPrefix() . $table;
        $blueprint = new Blueprint($prefixedTable);

        $callback($blueprint);

        $statements = $blueprint->toAlterSql($this->connection->getDriver());

        foreach ($statements as $sql) {
            $this->connection->statement($sql);
        }
    }

    /**
     * Drop a table.
     *
     * @param string $table Table name (without prefix)
     *
     * @throws \RuntimeException If the table does not exist
     */
    public function drop(string $table): void
    {
        $prefixedTable = $this->connection->getPrefix() . $table;
        $quoted = $this->quoteTable($prefixedTable);

        $this->connection->statement(sprintf('DROP TABLE %s', $quoted));
    }

    /**
     * Drop a table if it exists.
     *
     * @param string $table Table name (without prefix)
     */
    public function dropIfExists(string $table): void
    {
        $prefixedTable = $this->connection->getPrefix() . $table;
        $quoted = $this->quoteTable($prefixedTable);

        $this->connection->statement(sprintf('DROP TABLE IF EXISTS %s', $quoted));
    }

    /**
     * Rename a table.
     *
     * @param string $from Current table name (without prefix)
     * @param string $to New table name (without prefix)
     */
    public function rename(string $from, string $to): void
    {
        $prefix = $this->connection->getPrefix();
        $fromQuoted = $this->quoteTable($prefix . $from);
        $toQuoted = $this->quoteTable($prefix . $to);

        if ($this->connection->getDriver() === 'mysql') {
            $this->connection->statement(sprintf('RENAME TABLE %s TO %s', $fromQuoted, $toQuoted));
        } else {
            $this->connection->statement(sprintf('ALTER TABLE %s RENAME TO %s', $fromQuoted, $toQuoted));
        }
    }

    /**
     * Determine if a table exists.
     *
     * @param string $table Table name (without prefix)
     */
    public function hasTable(string $table): bool
    {
        return $this->connection->tableExists($table);
    }

    /**
     * Determine if a column exists on a table.
     *
     * @param string $table Table name (without prefix)
     * @param string $column Column name
     */
    public function hasColumn(string $table, string $column): bool
    {
        $columns = $this->getColumnListing($table);

        return in_array(strtolower($column), array_map('strtolower', $columns), true);
    }

    /**
     * Get the column listing for a table.
     *
     * @param string $table Table name (without prefix)
     * @return array<int, string> Column names
     */
    public function getColumnListing(string $table): array
    {
        $prefixedTable = $this->connection->getPrefix() . $table;
        $driver = $this->connection->getDriver();

        $rows = match ($driver) {
            'sqlite' => $this->connection->select(
                sprintf('PRAGMA table_info(%s)', $this->quoteTable($prefixedTable))
            ),
            'mysql' => $this->connection->select(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
                [$prefixedTable]
            ),
            'pgsql' => $this->connection->select(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? ORDER BY ordinal_position",
                [$prefixedTable]
            ),
            default => [],
        };

        $columns = [];

        foreach ($rows as $row) {
            if ($driver === 'sqlite') {
                $columns[] = (string) $row['name'];
            } elseif ($driver === 'mysql') {
                $columns[] = (string) $row['COLUMN_NAME'];
            } elseif ($driver === 'pgsql') {
                $columns[] = (string) $row['column_name'];
            }
        }

        return $columns;
    }

    /**
     * Quote a table name for the current driver.
     */
    private function quoteTable(string $table): string
    {
        return match ($this->connection->getDriver()) {
            'mysql' => '`' . str_replace('`', '``', $table) . '`',
            default => '"' . str_replace('"', '""', $table) . '"',
        };
    }
}
