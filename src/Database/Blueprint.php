<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Schema blueprint for defining table structure.
 *
 * Provides a fluent interface for defining columns, indexes, and
 * foreign keys during table creation or alteration. Compiles to
 * driver-specific SQL statements.
 */
final class Blueprint
{
    /** @var string Table name */
    private string $table;

    /** @var array<int, ColumnDefinition> Column definitions */
    private array $columns = [];

    /** @var array<int, array{type: string, columns: array<string>}> Index definitions */
    private array $indexes = [];

    /** @var array<int, ForeignKeyDefinition> Foreign key definitions */
    private array $foreignKeys = [];

    /** @var array<int, array{type: string, column?: string|array<string>, from?: string, to?: string}> Alteration commands */
    private array $commands = [];

    /**
     * @param string $table Table name
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // ========================================================================
    // Column Types
    // ========================================================================

    /**
     * Create an auto-incrementing big integer (BIGINT) primary key column.
     *
     * @param string $column Column name (defaults to 'id')
     * @return ColumnDefinition
     */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    /**
     * Create an auto-incrementing BIGINT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function bigIncrements(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'bigInteger');
        $col->isUnsigned = true;
        $col->isAutoIncrement = true;
        $col->isPrimary = true;

        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create an auto-incrementing INTEGER column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function increments(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'integer');
        $col->isUnsigned = true;
        $col->isAutoIncrement = true;
        $col->isPrimary = true;

        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create an INTEGER column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function integer(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'integer');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a BIGINT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function bigInteger(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'bigInteger');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a SMALLINT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function smallInteger(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'smallInteger');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a TINYINT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function tinyInteger(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'tinyInteger');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create an UNSIGNED INTEGER column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function unsignedInteger(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'integer');
        $col->isUnsigned = true;
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create an UNSIGNED BIGINT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function unsignedBigInteger(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'bigInteger');
        $col->isUnsigned = true;
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a FLOAT column.
     *
     * @param string $column Column name
     * @param int $precision Total number of digits
     * @param int $scale Number of digits after the decimal point
     * @return ColumnDefinition
     */
    public function float(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'float');
        $col->precision = $precision;
        $col->scale = $scale;
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a DOUBLE column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function double(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'double');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a DECIMAL column.
     *
     * @param string $column Column name
     * @param int $precision Total number of digits
     * @param int $scale Number of digits after the decimal point
     * @return ColumnDefinition
     */
    public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'decimal');
        $col->precision = $precision;
        $col->scale = $scale;
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a VARCHAR column.
     *
     * @param string $column Column name
     * @param int $length Maximum length
     * @return ColumnDefinition
     */
    public function string(string $column, int $length = 255): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'string');
        $col->length = $length;
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a TEXT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function text(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'text');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a MEDIUMTEXT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function mediumText(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'mediumText');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a LONGTEXT column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function longText(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'longText');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a BOOLEAN column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function boolean(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'boolean');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a DATE column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function date(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'date');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a DATETIME column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function dateTime(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'dateTime');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a TIMESTAMP column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function timestamp(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'timestamp');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create created_at and updated_at timestamp columns.
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable()->useCurrent();
        $this->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
    }

    /**
     * Create a deleted_at nullable timestamp column for soft deletes.
     */
    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')->nullable();
    }

    /**
     * Create a JSON column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function json(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'json');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a BINARY/BLOB column.
     *
     * @param string $column Column name
     * @return ColumnDefinition
     */
    public function binary(string $column): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'binary');
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create an ENUM column.
     *
     * @param string $column Column name
     * @param array<string> $allowed Allowed values
     * @return ColumnDefinition
     */
    public function enum(string $column, array $allowed): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'enum');
        $col->allowed = $allowed;
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Create a UUID (CHAR(36)) column.
     *
     * @param string $column Column name (defaults to 'uuid')
     * @return ColumnDefinition
     */
    public function uuid(string $column = 'uuid'): ColumnDefinition
    {
        $col = new ColumnDefinition($column, 'uuid');
        $this->columns[] = $col;

        return $col;
    }

    // ========================================================================
    // Indexes
    // ========================================================================

    /**
     * Add a primary key constraint.
     *
     * @param string|array<string> $columns Column(s) for the primary key
     * @return static
     */
    public function primary(string|array $columns): static
    {
        $this->indexes[] = [
            'type' => 'primary',
            'columns' => (array) $columns,
        ];

        return $this;
    }

    /**
     * Add a unique index.
     *
     * @param string|array<string> $columns Column(s) for the unique index
     * @return static
     */
    public function unique(string|array $columns): static
    {
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => (array) $columns,
        ];

        return $this;
    }

    /**
     * Add an index.
     *
     * @param string|array<string> $columns Column(s) to index
     * @return static
     */
    public function index(string|array $columns): static
    {
        $this->indexes[] = [
            'type' => 'index',
            'columns' => (array) $columns,
        ];

        return $this;
    }

    /**
     * Define a foreign key constraint.
     *
     * @param string $column The local column holding the foreign key
     * @return ForeignKeyDefinition
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $fk;

        return $fk;
    }

    // ========================================================================
    // Modify (Alter table operations)
    // ========================================================================

    /**
     * Drop one or more columns.
     *
     * @param string|array<string> $columns Column name(s) to drop
     */
    public function dropColumn(string|array $columns): void
    {
        $this->commands[] = [
            'type' => 'dropColumn',
            'column' => $columns,
        ];
    }

    /**
     * Rename a column.
     *
     * @param string $from Current column name
     * @param string $to New column name
     */
    public function renameColumn(string $from, string $to): void
    {
        $this->commands[] = [
            'type' => 'renameColumn',
            'from' => $from,
            'to' => $to,
        ];
    }

    // ========================================================================
    // SQL Compilation
    // ========================================================================

    /**
     * Compile the blueprint to driver-specific SQL statements.
     *
     * @param string $driver Database driver (mysql, pgsql, sqlite)
     * @return array<int, string> Array of SQL statements
     */
    public function toSql(string $driver): array
    {
        $statements = [];

        // Compile column additions as CREATE TABLE or ALTER TABLE ADD
        if ($this->columns !== []) {
            $columnDefs = [];
            $tablePrimaryKey = null;

            foreach ($this->columns as $column) {
                $columnDefs[] = $this->compileColumn($column, $driver);

                if ($column->isPrimary && !$column->isAutoIncrement) {
                    $tablePrimaryKey = $column->name;
                }
            }

            // For CREATE TABLE context
            $sql = sprintf(
                'CREATE TABLE %s (%s',
                $this->quoteTable($driver),
                implode(', ', $columnDefs)
            );

            // Add table-level primary key if not inline
            if ($tablePrimaryKey !== null) {
                $sql .= sprintf(', PRIMARY KEY (%s)', $this->quoteColumn($tablePrimaryKey, $driver));
            }

            // Add indexes inline
            foreach ($this->indexes as $index) {
                $cols = implode(', ', array_map(fn(string $c): string => $this->quoteColumn($c, $driver), $index['columns']));

                $sql .= match ($index['type']) {
                    'primary' => sprintf(', PRIMARY KEY (%s)', $cols),
                    'unique' => sprintf(', UNIQUE (%s)', $cols),
                    'index' => '', // Indexes are added as separate statements
                };
            }

            // Add foreign keys inline
            foreach ($this->foreignKeys as $fk) {
                if ($fk->referencedTable !== null && $fk->referencedColumn !== null) {
                    $sql .= sprintf(
                        ', FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
                        $this->quoteColumn($fk->column, $driver),
                        $this->quoteTableName($fk->referencedTable, $driver),
                        $this->quoteColumn($fk->referencedColumn, $driver),
                        $fk->onDelete,
                        $fk->onUpdate
                    );
                }
            }

            $sql .= ')';

            // Add engine for MySQL
            if ($driver === 'mysql') {
                $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
            }

            $statements[] = $sql;

            // Create separate index statements for non-unique indexes
            foreach ($this->indexes as $index) {
                if ($index['type'] === 'index') {
                    $cols = implode(', ', array_map(fn(string $c): string => $this->quoteColumn($c, $driver), $index['columns']));
                    $indexName = sprintf('idx_%s_%s', $this->table, implode('_', $index['columns']));
                    $statements[] = sprintf(
                        'CREATE INDEX %s ON %s (%s)',
                        $this->quoteColumnName($indexName, $driver),
                        $this->quoteTable($driver),
                        $cols
                    );
                }
            }

            // Add indexes from column-level definitions
            foreach ($this->columns as $column) {
                if ($column->isUnique && !$column->isPrimary) {
                    $indexName = sprintf('uniq_%s_%s', $this->table, $column->name);
                    $statements[] = sprintf(
                        'CREATE UNIQUE INDEX %s ON %s (%s)',
                        $this->quoteColumnName($indexName, $driver),
                        $this->quoteTable($driver),
                        $this->quoteColumn($column->name, $driver)
                    );
                }

                if ($column->isIndex) {
                    $indexName = sprintf('idx_%s_%s', $this->table, $column->name);
                    $statements[] = sprintf(
                        'CREATE INDEX %s ON %s (%s)',
                        $this->quoteColumnName($indexName, $driver),
                        $this->quoteTable($driver),
                        $this->quoteColumn($column->name, $driver)
                    );
                }
            }
        }

        // Compile alteration commands
        foreach ($this->commands as $command) {
            match ($command['type']) {
                'dropColumn' => $this->compileDropColumn($command, $driver, $statements),
                'renameColumn' => $this->compileRenameColumn($command, $driver, $statements),
                default => null,
            };
        }

        return $statements;
    }

    /**
     * Get the defined columns.
     *
     * @return array<int, ColumnDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the defined commands.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get the defined foreign keys.
     *
     * @return array<int, ForeignKeyDefinition>
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    // ========================================================================
    // Internal compilation helpers
    // ========================================================================

    /**
     * Compile a single column definition to SQL.
     */
    private function compileColumn(ColumnDefinition $column, string $driver): string
    {
        $parts = [];

        // Column name
        $parts[] = $this->quoteColumn($column->name, $driver);

        // Type mapping
        $parts[] = $this->mapColumnType($column, $driver);

        // Unsigned (MySQL only)
        if ($column->isUnsigned && $driver === 'mysql' && !$column->isAutoIncrement) {
            $parts[] = 'UNSIGNED';
        }

        // AUTO_INCREMENT / Primary Key
        if ($column->isAutoIncrement) {
            if ($driver === 'sqlite') {
                $parts[] = 'PRIMARY KEY AUTOINCREMENT';
            } elseif ($driver === 'mysql') {
                if ($column->isUnsigned) {
                    // Need to add UNSIGNED before AUTO_INCREMENT for MySQL
                    $parts[] = 'UNSIGNED';
                }
                $parts[] = 'AUTO_INCREMENT PRIMARY KEY';
            } elseif ($driver === 'pgsql') {
                // For pgsql, SERIAL/BIGSERIAL already implies auto-increment
                // The type is already set by mapColumnType
                $parts[] = 'PRIMARY KEY';
            }
        }

        // NOT NULL / NULL
        if (!$column->isAutoIncrement) {
            $parts[] = $column->isNullable ? 'NULL' : 'NOT NULL';
        }

        // DEFAULT
        if ($column->useCurrent) {
            $parts[] = 'DEFAULT CURRENT_TIMESTAMP';
        } elseif ($column->hasDefault) {
            $parts[] = 'DEFAULT ' . $this->compileDefaultValue($column->defaultValue, $driver);
        }

        // ON UPDATE CURRENT_TIMESTAMP (MySQL only)
        if ($column->useCurrentOnUpdate && $driver === 'mysql') {
            $parts[] = 'ON UPDATE CURRENT_TIMESTAMP';
        }

        // Character set (MySQL only)
        if ($column->charset !== null && $driver === 'mysql') {
            $parts[] = sprintf('CHARACTER SET %s', $column->charset);
        }

        // Collation (MySQL only)
        if ($column->collation !== null && $driver === 'mysql') {
            $parts[] = sprintf('COLLATE %s', $column->collation);
        }

        // Comment (MySQL only)
        if ($column->comment !== null && $driver === 'mysql') {
            $parts[] = sprintf("COMMENT '%s'", str_replace("'", "''", $column->comment));
        }

        // AFTER / FIRST (MySQL only, for ALTER TABLE)
        if ($column->afterColumn !== null && $driver === 'mysql') {
            $parts[] = sprintf('AFTER %s', $this->quoteColumn($column->afterColumn, $driver));
        }

        if ($column->isFirst && $driver === 'mysql') {
            $parts[] = 'FIRST';
        }

        return implode(' ', $parts);
    }

    /**
     * Map a column type to its driver-specific SQL type.
     */
    private function mapColumnType(ColumnDefinition $column, string $driver): string
    {
        return match ($column->type) {
            'integer' => match ($driver) {
                'pgsql' => $column->isAutoIncrement ? 'SERIAL' : 'INTEGER',
                default => 'INTEGER',
            },
            'bigInteger' => match ($driver) {
                'pgsql' => $column->isAutoIncrement ? 'BIGSERIAL' : 'BIGINT',
                'sqlite' => 'INTEGER',
                default => 'BIGINT',
            },
            'smallInteger' => match ($driver) {
                default => 'SMALLINT',
            },
            'tinyInteger' => match ($driver) {
                'pgsql', 'sqlite' => 'SMALLINT',
                default => 'TINYINT',
            },
            'float' => sprintf(
                'FLOAT(%d, %d)',
                $column->precision ?? 8,
                $column->scale ?? 2
            ),
            'double' => 'DOUBLE PRECISION',
            'decimal' => sprintf(
                'DECIMAL(%d, %d)',
                $column->precision ?? 8,
                $column->scale ?? 2
            ),
            'string' => match ($driver) {
                default => sprintf('VARCHAR(%d)', $column->length ?? 255),
            },
            'text' => 'TEXT',
            'mediumText' => match ($driver) {
                'mysql' => 'MEDIUMTEXT',
                default => 'TEXT',
            },
            'longText' => match ($driver) {
                'mysql' => 'LONGTEXT',
                default => 'TEXT',
            },
            'boolean' => match ($driver) {
                'mysql' => 'TINYINT(1)',
                'pgsql' => 'BOOLEAN',
                'sqlite' => 'INTEGER',
                default => 'BOOLEAN',
            },
            'date' => 'DATE',
            'dateTime' => match ($driver) {
                'pgsql' => 'TIMESTAMP',
                default => 'DATETIME',
            },
            'timestamp' => match ($driver) {
                'mysql' => 'TIMESTAMP',
                'pgsql' => 'TIMESTAMP',
                'sqlite' => 'DATETIME',
                default => 'TIMESTAMP',
            },
            'json' => match ($driver) {
                'mysql', 'pgsql' => 'JSON',
                'sqlite' => 'TEXT',
                default => 'JSON',
            },
            'binary' => match ($driver) {
                'mysql' => 'BLOB',
                'pgsql' => 'BYTEA',
                'sqlite' => 'BLOB',
                default => 'BLOB',
            },
            'enum' => match ($driver) {
                'mysql' => sprintf(
                    "ENUM(%s)",
                    implode(', ', array_map(fn(string $v): string => "'" . str_replace("'", "''", $v) . "'", $column->allowed ?? []))
                ),
                'pgsql' => 'VARCHAR(255)',
                'sqlite' => 'VARCHAR(255)',
                default => 'VARCHAR(255)',
            },
            'uuid' => match ($driver) {
                'pgsql' => 'UUID',
                default => 'CHAR(36)',
            },
            default => 'VARCHAR(255)',
        };
    }

    /**
     * Compile a default value into SQL representation.
     */
    private function compileDefaultValue(mixed $value, string $driver): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            if ($driver === 'pgsql') {
                return $value ? 'TRUE' : 'FALSE';
            }
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return sprintf("'%s'", str_replace("'", "''", (string) $value));
    }

    /**
     * Compile a DROP COLUMN command.
     *
     * @param array<string, mixed> $command
     * @param string $driver
     * @param array<int, string> &$statements
     */
    private function compileDropColumn(array $command, string $driver, array &$statements): void
    {
        $columns = (array) $command['column'];

        if ($driver === 'sqlite') {
            // SQLite does not support DROP COLUMN before 3.35.0
            // For broader compatibility, each column gets its own ALTER TABLE
            foreach ($columns as $column) {
                $statements[] = sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $this->quoteTable($driver),
                    $this->quoteColumn($column, $driver)
                );
            }
        } elseif ($driver === 'mysql') {
            foreach ($columns as $column) {
                $statements[] = sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $this->quoteTable($driver),
                    $this->quoteColumn($column, $driver)
                );
            }
        } else {
            foreach ($columns as $column) {
                $statements[] = sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $this->quoteTable($driver),
                    $this->quoteColumn($column, $driver)
                );
            }
        }
    }

    /**
     * Compile a RENAME COLUMN command.
     *
     * @param array<string, mixed> $command
     * @param string $driver
     * @param array<int, string> &$statements
     */
    private function compileRenameColumn(array $command, string $driver, array &$statements): void
    {
        $statements[] = sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->quoteTable($driver),
            $this->quoteColumn((string) $command['from'], $driver),
            $this->quoteColumn((string) $command['to'], $driver)
        );
    }

    /**
     * Quote the table name for the target driver.
     */
    private function quoteTable(string $driver): string
    {
        return $this->quoteTableName($this->table, $driver);
    }

    /**
     * Quote a table name for the target driver.
     */
    private function quoteTableName(string $table, string $driver): string
    {
        return match ($driver) {
            'mysql' => '`' . str_replace('`', '``', $table) . '`',
            default => '"' . str_replace('"', '""', $table) . '"',
        };
    }

    /**
     * Quote a column name for the target driver.
     */
    private function quoteColumn(string $column, string $driver): string
    {
        return $this->quoteColumnName($column, $driver);
    }

    /**
     * Quote any identifier name for the target driver.
     */
    private function quoteColumnName(string $name, string $driver): string
    {
        return match ($driver) {
            'mysql' => '`' . str_replace('`', '``', $name) . '`',
            default => '"' . str_replace('"', '""', $name) . '"',
        };
    }

    /**
     * Compile ALTER TABLE ADD COLUMN statements (used for table() alteration).
     *
     * @param string $driver Database driver
     * @return array<int, string> SQL statements
     */
    public function toAlterSql(string $driver): array
    {
        $statements = [];

        // Add new columns
        foreach ($this->columns as $column) {
            $statements[] = sprintf(
                'ALTER TABLE %s ADD COLUMN %s',
                $this->quoteTable($driver),
                $this->compileColumn($column, $driver)
            );
        }

        // Add indexes
        foreach ($this->indexes as $index) {
            $cols = implode(', ', array_map(fn(string $c): string => $this->quoteColumn($c, $driver), $index['columns']));

            if ($index['type'] === 'unique') {
                $indexName = sprintf('uniq_%s_%s', $this->table, implode('_', $index['columns']));
                $statements[] = sprintf(
                    'CREATE UNIQUE INDEX %s ON %s (%s)',
                    $this->quoteColumnName($indexName, $driver),
                    $this->quoteTable($driver),
                    $cols
                );
            } elseif ($index['type'] === 'index') {
                $indexName = sprintf('idx_%s_%s', $this->table, implode('_', $index['columns']));
                $statements[] = sprintf(
                    'CREATE INDEX %s ON %s (%s)',
                    $this->quoteColumnName($indexName, $driver),
                    $this->quoteTable($driver),
                    $cols
                );
            }
        }

        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            if ($fk->referencedTable !== null && $fk->referencedColumn !== null) {
                $constraintName = sprintf('fk_%s_%s', $this->table, $fk->column);

                if ($driver === 'sqlite') {
                    // SQLite does not support ADD CONSTRAINT for foreign keys
                    // Foreign keys must be defined at table creation time
                    continue;
                }

                $statements[] = sprintf(
                    'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
                    $this->quoteTable($driver),
                    $this->quoteColumnName($constraintName, $driver),
                    $this->quoteColumn($fk->column, $driver),
                    $this->quoteTableName($fk->referencedTable, $driver),
                    $this->quoteColumn($fk->referencedColumn, $driver),
                    $fk->onDelete,
                    $fk->onUpdate
                );
            }
        }

        // Compile modification commands (dropColumn, renameColumn)
        foreach ($this->commands as $command) {
            match ($command['type']) {
                'dropColumn' => $this->compileDropColumn($command, $driver, $statements),
                'renameColumn' => $this->compileRenameColumn($command, $driver, $statements),
                default => null,
            };
        }

        // Column-level indexes from new columns
        foreach ($this->columns as $column) {
            if ($column->isUnique && !$column->isPrimary) {
                $indexName = sprintf('uniq_%s_%s', $this->table, $column->name);
                $statements[] = sprintf(
                    'CREATE UNIQUE INDEX %s ON %s (%s)',
                    $this->quoteColumnName($indexName, $driver),
                    $this->quoteTable($driver),
                    $this->quoteColumn($column->name, $driver)
                );
            }

            if ($column->isIndex) {
                $indexName = sprintf('idx_%s_%s', $this->table, $column->name);
                $statements[] = sprintf(
                    'CREATE INDEX %s ON %s (%s)',
                    $this->quoteColumnName($indexName, $driver),
                    $this->quoteTable($driver),
                    $this->quoteColumn($column->name, $driver)
                );
            }
        }

        return $statements;
    }
}
