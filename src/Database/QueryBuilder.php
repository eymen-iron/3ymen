<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Fluent SQL query builder.
 *
 * Provides a chainable interface for constructing SQL queries with proper
 * parameter binding. Supports SELECT, INSERT, UPDATE, DELETE operations
 * along with joins, grouping, ordering, pagination, and aggregates.
 */
final class QueryBuilder
{
    private Connection $connection;
    private string $table;

    /** @var array<int, string> */
    private array $columns = ['*'];

    /** @var array<int, array{type: string, column?: string, operator?: string, value?: mixed, sql?: string, boolean: string}> */
    private array $wheres = [];

    /** @var array<int, mixed> */
    private array $bindings = [];

    /** @var array<int, array{column: string, direction: string}> */
    private array $orders = [];

    /** @var array<int, string> */
    private array $groups = [];

    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    /** @var array<int, array{type: string, table: string, first: string, operator: string, second: string}> */
    private array $joins = [];

    private ?string $havingClause = null;

    /** @var array<int, mixed> */
    private array $havingBindings = [];

    private bool $distinct = false;

    private ?string $rawSelect = null;

    /** @var array<int, mixed> */
    private array $rawSelectBindings = [];

    /**
     * Create a new query builder instance.
     *
     * @param Connection $connection The database connection
     * @param string $table The table to query (without prefix)
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $connection->getPrefix() . $table;
    }

    // ========================================================================
    // SELECT
    // ========================================================================

    /**
     * Set the columns to select.
     *
     * @param string ...$columns Column names
     * @return static
     */
    public function select(string ...$columns): static
    {
        $this->columns = $columns ?: ['*'];

        return $this;
    }

    /**
     * Set the query to return distinct results.
     *
     * @return static
     */
    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    /**
     * Add additional columns to the select clause.
     *
     * @param string ...$columns Column names to add
     * @return static
     */
    public function addSelect(string ...$columns): static
    {
        if ($this->columns === ['*']) {
            $this->columns = [];
        }

        foreach ($columns as $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    // ========================================================================
    // WHERE
    // ========================================================================

    /**
     * Add a WHERE clause.
     *
     * When called with two arguments, the operator defaults to '='.
     *
     * @param string $column Column name
     * @param mixed $operator Comparison operator or value (when used as 2-arg form)
     * @param mixed $value The value to compare against
     * @return static
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => (string) $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add an OR WHERE clause.
     *
     * @param string $column Column name
     * @param mixed $operator Comparison operator or value
     * @param mixed $value The value to compare against
     * @return static
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => (string) $operator,
            'value' => $value,
            'boolean' => 'OR',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add a WHERE IN clause.
     *
     * @param string $column Column name
     * @param array<mixed> $values Values to check against
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'value' => $values,
            'boolean' => 'AND',
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Add a WHERE NOT IN clause.
     *
     * @param string $column Column name
     * @param array<mixed> $values Values to exclude
     * @return static
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'value' => $values,
            'boolean' => 'AND',
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Add a WHERE IS NULL clause.
     *
     * @param string $column Column name
     * @return static
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE IS NOT NULL clause.
     *
     * @param string $column Column name
     * @return static
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE BETWEEN clause.
     *
     * @param string $column Column name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @return static
     */
    public function whereBetween(string $column, mixed $min, mixed $max): static
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'boolean' => 'AND',
        ];

        $this->bindings[] = $min;
        $this->bindings[] = $max;

        return $this;
    }

    /**
     * Add a raw WHERE clause.
     *
     * @param string $sql Raw SQL expression
     * @param array<mixed> $bindings Parameter bindings for the expression
     * @return static
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND',
        ];

        foreach ($bindings as $binding) {
            $this->bindings[] = $binding;
        }

        return $this;
    }

    /**
     * Add a WHERE LIKE clause.
     *
     * @param string $column Column name
     * @param string $value LIKE pattern (e.g., '%search%')
     * @return static
     */
    public function whereLike(string $column, string $value): static
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'LIKE',
            'value' => $value,
            'boolean' => 'AND',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    // ========================================================================
    // JOINS
    // ========================================================================

    /**
     * Add an INNER JOIN clause.
     *
     * @param string $table Table to join
     * @param string $first First column (left side)
     * @param string $operator Comparison operator
     * @param string $second Second column (right side)
     * @return static
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $this->connection->getPrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Comparison operator
     * @param string $second Second column
     * @return static
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $this->connection->getPrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a RIGHT JOIN clause.
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Comparison operator
     * @param string $second Second column
     * @return static
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $this->connection->getPrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a CROSS JOIN clause.
     *
     * @param string $table Table to cross join
     * @return static
     */
    public function crossJoin(string $table): static
    {
        $this->joins[] = [
            'type' => 'CROSS',
            'table' => $this->connection->getPrefix() . $table,
            'first' => '',
            'operator' => '',
            'second' => '',
        ];

        return $this;
    }

    // ========================================================================
    // ORDER / GROUP / LIMIT
    // ========================================================================

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column Column to order by
     * @param string $direction Sort direction ('asc' or 'desc')
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Order by a column in descending order.
     *
     * @param string $column Column to order by (defaults to 'created_at')
     * @return static
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Order by a column in ascending order.
     *
     * @param string $column Column to order by (defaults to 'created_at')
     * @return static
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Add GROUP BY columns.
     *
     * @param string ...$columns Columns to group by
     * @return static
     */
    public function groupBy(string ...$columns): static
    {
        foreach ($columns as $column) {
            $this->groups[] = $column;
        }

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * @param string $column Column name or aggregate expression
     * @param string $operator Comparison operator
     * @param mixed $value The value to compare against
     * @return static
     */
    public function having(string $column, string $operator, mixed $value): static
    {
        $this->havingClause = sprintf('%s %s ?', $column, $operator);
        $this->havingBindings[] = $value;

        return $this;
    }

    /**
     * Set the maximum number of results to return.
     *
     * @param int $limit Maximum number of rows
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limitValue = max(0, $limit);

        return $this;
    }

    /**
     * Set the number of results to skip.
     *
     * @param int $offset Number of rows to skip
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offsetValue = max(0, $offset);

        return $this;
    }

    /**
     * Alias for limit().
     *
     * @param int $limit Maximum number of rows
     * @return static
     */
    public function take(int $limit): static
    {
        return $this->limit($limit);
    }

    /**
     * Alias for offset().
     *
     * @param int $offset Number of rows to skip
     * @return static
     */
    public function skip(int $offset): static
    {
        return $this->offset($offset);
    }

    // ========================================================================
    // EXECUTE (SELECT)
    // ========================================================================

    /**
     * Execute the query and return all results.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = $this->compileSelect();
        $bindings = $this->getAllBindings();

        return $this->connection->select($sql, $bindings);
    }

    /**
     * Execute the query and return the first result.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();

        return $results[0] ?? null;
    }

    /**
     * Find a record by its primary key.
     *
     * @param mixed $id The primary key value
     * @param string $primaryKey The primary key column name
     * @return array<string, mixed>|null
     */
    public function find(mixed $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, '=', $id)->first();
    }

    /**
     * Get a single column value from the first result.
     *
     * @param string $column Column name
     * @return mixed The column value, or null if no result
     */
    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();

        return $result[$column] ?? null;
    }

    /**
     * Get an array of values for a single column, optionally keyed by another column.
     *
     * @param string $column Column to pluck values from
     * @param string|null $key Column to use as array keys
     * @return array<mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $selectColumns = $key !== null ? [$column, $key] : [$column];
        $results = $this->select(...$selectColumns)->get();

        $plucked = [];

        foreach ($results as $row) {
            if ($key !== null) {
                $plucked[$row[$key]] = $row[$column];
            } else {
                $plucked[] = $row[$column];
            }
        }

        return $plucked;
    }

    /**
     * Determine if any rows exist for the current query.
     */
    public function exists(): bool
    {
        return $this->first() !== null;
    }

    /**
     * Determine if no rows exist for the current query.
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Get the count of results.
     *
     * @param string $column Column to count (defaults to '*')
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * Get the maximum value of a column.
     *
     * @param string $column Column name
     * @return mixed The maximum value
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Get the minimum value of a column.
     *
     * @param string $column Column name
     * @return mixed The minimum value
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get the average value of a column.
     *
     * @param string $column Column name
     */
    public function avg(string $column): float
    {
        return (float) ($this->aggregate('AVG', $column) ?? 0);
    }

    /**
     * Get the sum of a column.
     *
     * @param string $column Column name
     */
    public function sum(string $column): float
    {
        return (float) ($this->aggregate('SUM', $column) ?? 0);
    }

    // ========================================================================
    // INSERT / UPDATE / DELETE
    // ========================================================================

    /**
     * Insert a new record into the table.
     *
     * @param array<string, mixed> $values Column-value pairs to insert
     * @return bool Whether the insert succeeded
     */
    public function insert(array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $columns = array_keys($values);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', array_map(fn(string $col): string => $this->quoteIdentifier($col), $columns));

        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $columnList, $placeholders);

        return $this->connection->insert($sql, array_values($values));
    }

    /**
     * Insert a new record and return the auto-generated ID.
     *
     * @param array<string, mixed> $values Column-value pairs to insert
     * @return int|string The last insert ID
     */
    public function insertGetId(array $values): int|string
    {
        $this->insert($values);
        $id = $this->connection->lastInsertId();

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Update records matching the current WHERE clauses.
     *
     * @param array<string, mixed> $values Column-value pairs to update
     * @return int Number of affected rows
     */
    public function update(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        $setClauses = [];
        $setBindings = [];

        foreach ($values as $column => $value) {
            $setClauses[] = sprintf('%s = ?', $this->quoteIdentifier($column));
            $setBindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $setClauses));

        $whereClause = $this->compileWheres();
        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }

        $allBindings = array_merge($setBindings, $this->bindings);

        return $this->connection->update($sql, $allBindings);
    }

    /**
     * Delete records matching the current WHERE clauses.
     *
     * @return int Number of affected rows
     */
    public function delete(): int
    {
        $sql = sprintf('DELETE FROM %s', $this->table);

        $whereClause = $this->compileWheres();
        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }

        return $this->connection->delete($sql, $this->bindings);
    }

    /**
     * Increment a column value.
     *
     * @param string $column Column to increment
     * @param int|float $amount Amount to increment by
     * @param array<string, mixed> $extra Additional columns to update
     * @return int Number of affected rows
     */
    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $setClauses = [sprintf('%s = %s + ?', $this->quoteIdentifier($column), $this->quoteIdentifier($column))];
        $setBindings = [$amount];

        foreach ($extra as $col => $value) {
            $setClauses[] = sprintf('%s = ?', $this->quoteIdentifier($col));
            $setBindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $setClauses));

        $whereClause = $this->compileWheres();
        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }

        $allBindings = array_merge($setBindings, $this->bindings);

        return $this->connection->update($sql, $allBindings);
    }

    /**
     * Decrement a column value.
     *
     * @param string $column Column to decrement
     * @param int|float $amount Amount to decrement by
     * @param array<string, mixed> $extra Additional columns to update
     * @return int Number of affected rows
     */
    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        $setClauses = [sprintf('%s = %s - ?', $this->quoteIdentifier($column), $this->quoteIdentifier($column))];
        $setBindings = [$amount];

        foreach ($extra as $col => $value) {
            $setClauses[] = sprintf('%s = ?', $this->quoteIdentifier($col));
            $setBindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $setClauses));

        $whereClause = $this->compileWheres();
        if ($whereClause !== '') {
            $sql .= ' WHERE ' . $whereClause;
        }

        $allBindings = array_merge($setBindings, $this->bindings);

        return $this->connection->update($sql, $allBindings);
    }

    /**
     * Truncate the table (delete all rows and reset auto-increment).
     */
    public function truncate(): void
    {
        $driver = $this->connection->getDriver();

        if ($driver === 'sqlite') {
            $this->connection->statement(sprintf('DELETE FROM %s', $this->table));
            $this->connection->statement("DELETE FROM sqlite_sequence WHERE name = ?", [$this->table]);
        } else {
            $this->connection->statement(sprintf('TRUNCATE TABLE %s', $this->table));
        }
    }

    // ========================================================================
    // RAW
    // ========================================================================

    /**
     * Set a raw expression for the query (used internally).
     *
     * @param string $expression Raw SQL expression
     * @return static
     */
    public function raw(string $expression): static
    {
        $this->rawSelect = $expression;

        return $this;
    }

    /**
     * Set a raw SELECT expression.
     *
     * @param string $expression Raw SQL expression for the SELECT clause
     * @param array<mixed> $bindings Parameter bindings
     * @return static
     */
    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->rawSelect = $expression;
        $this->rawSelectBindings = $bindings;

        return $this;
    }

    // ========================================================================
    // PAGINATION
    // ========================================================================

    /**
     * Paginate the query results.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number (1-based)
     * @return array{data: array<int, array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $total = $this->count();

        $results = $this->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    // ========================================================================
    // CHUNK
    // ========================================================================

    /**
     * Process results in chunks.
     *
     * The callback receives each chunk (array of rows) and may return false
     * to stop processing further chunks.
     *
     * @param int $count Number of rows per chunk
     * @param callable $callback Function receiving each chunk
     * @return bool Whether all chunks were processed (true if callback never returned false)
     */
    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->limit($count)
                ->offset(($page - 1) * $count)
                ->get();

            $countResults = count($results);

            if ($countResults === 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while ($countResults === $count);

        return true;
    }

    // ========================================================================
    // SQL BUILD (Public)
    // ========================================================================

    /**
     * Get the compiled SQL string without executing.
     *
     * @return string The compiled SQL query
     */
    public function toSql(): string
    {
        return $this->compileSelect();
    }

    /**
     * Get all parameter bindings.
     *
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return $this->getAllBindings();
    }

    // ========================================================================
    // Internal SQL Compilation
    // ========================================================================

    /**
     * Compile the full SELECT statement.
     */
    private function compileSelect(): string
    {
        $parts = [];

        // SELECT
        $selectKeyword = $this->distinct ? 'SELECT DISTINCT' : 'SELECT';

        if ($this->rawSelect !== null) {
            $parts[] = $selectKeyword . ' ' . $this->rawSelect;
        } else {
            $parts[] = $selectKeyword . ' ' . implode(', ', $this->columns);
        }

        // FROM
        $parts[] = 'FROM ' . $this->table;

        // JOINS
        $joins = $this->compileJoins();
        if ($joins !== '') {
            $parts[] = $joins;
        }

        // WHERE
        $wheres = $this->compileWheres();
        if ($wheres !== '') {
            $parts[] = 'WHERE ' . $wheres;
        }

        // GROUP BY
        $groups = $this->compileGroups();
        if ($groups !== '') {
            $parts[] = $groups;
        }

        // HAVING
        if ($this->havingClause !== null) {
            $parts[] = 'HAVING ' . $this->havingClause;
        }

        // ORDER BY
        $orders = $this->compileOrders();
        if ($orders !== '') {
            $parts[] = $orders;
        }

        // LIMIT / OFFSET
        $limit = $this->compileLimit();
        if ($limit !== '') {
            $parts[] = $limit;
        }

        return implode(' ', $parts);
    }

    /**
     * Compile WHERE clauses into a SQL string.
     */
    private function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $parts = [];

        foreach ($this->wheres as $index => $where) {
            $clause = match ($where['type']) {
                'basic' => sprintf('%s %s ?', $where['column'], $where['operator']),
                'in' => sprintf('%s IN (%s)', $where['column'], implode(', ', array_fill(0, count($where['value']), '?'))),
                'not_in' => sprintf('%s NOT IN (%s)', $where['column'], implode(', ', array_fill(0, count($where['value']), '?'))),
                'null' => sprintf('%s IS NULL', $where['column']),
                'not_null' => sprintf('%s IS NOT NULL', $where['column']),
                'between' => sprintf('%s BETWEEN ? AND ?', $where['column']),
                'raw' => $where['sql'],
                default => '',
            };

            if ($clause === '') {
                continue;
            }

            if ($index === 0) {
                $parts[] = $clause;
            } else {
                $parts[] = $where['boolean'] . ' ' . $clause;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Compile JOIN clauses into a SQL string.
     */
    private function compileJoins(): string
    {
        if ($this->joins === []) {
            return '';
        }

        $parts = [];

        foreach ($this->joins as $join) {
            if ($join['type'] === 'CROSS') {
                $parts[] = sprintf('CROSS JOIN %s', $join['table']);
            } else {
                $parts[] = sprintf(
                    '%s JOIN %s ON %s %s %s',
                    $join['type'],
                    $join['table'],
                    $join['first'],
                    $join['operator'],
                    $join['second']
                );
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Compile ORDER BY clauses into a SQL string.
     */
    private function compileOrders(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $parts = array_map(
            fn(array $order): string => $order['column'] . ' ' . $order['direction'],
            $this->orders
        );

        return 'ORDER BY ' . implode(', ', $parts);
    }

    /**
     * Compile GROUP BY clauses into a SQL string.
     */
    private function compileGroups(): string
    {
        if ($this->groups === []) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', $this->groups);
    }

    /**
     * Compile LIMIT and OFFSET into a SQL string.
     */
    private function compileLimit(): string
    {
        $parts = [];

        if ($this->limitValue !== null) {
            $parts[] = 'LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $parts[] = 'OFFSET ' . $this->offsetValue;
        }

        return implode(' ', $parts);
    }

    /**
     * Get all bindings merged in proper order.
     *
     * @return array<int, mixed>
     */
    private function getAllBindings(): array
    {
        return array_merge($this->rawSelectBindings, $this->bindings, $this->havingBindings);
    }

    /**
     * Run an aggregate function and return the result.
     *
     * @param string $function Aggregate function (COUNT, MAX, etc.)
     * @param string $column Column name
     * @return mixed The aggregate result
     */
    private function aggregate(string $function, string $column): mixed
    {
        $alias = strtolower($function) . '_result';
        $originalColumns = $this->columns;
        $originalLimit = $this->limitValue;
        $originalOffset = $this->offsetValue;

        $this->columns = [sprintf('%s(%s) AS %s', $function, $column, $alias)];
        $this->limitValue = null;
        $this->offsetValue = null;

        $result = $this->get();

        // Restore original state
        $this->columns = $originalColumns;
        $this->limitValue = $originalLimit;
        $this->offsetValue = $originalOffset;

        return $result[0][$alias] ?? null;
    }

    /**
     * Quote an identifier (column or table name) for safe SQL embedding.
     *
     * @param string $identifier The identifier to quote
     * @return string The quoted identifier
     */
    private function quoteIdentifier(string $identifier): string
    {
        // Don't quote if it contains dots (qualified names), expressions, or is already quoted
        if (str_contains($identifier, '.') || str_contains($identifier, '(') || str_contains($identifier, '`') || str_contains($identifier, '"')) {
            return $identifier;
        }

        return match ($this->connection->getDriver()) {
            'mysql' => '`' . str_replace('`', '``', $identifier) . '`',
            default => '"' . str_replace('"', '""', $identifier) . '"',
        };
    }
}
