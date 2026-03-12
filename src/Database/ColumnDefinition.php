<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Represents a column definition within a Blueprint.
 *
 * Provides fluent modifiers for column attributes such as nullability,
 * default values, indexes, and auto-increment. Used during schema
 * creation and alteration.
 */
final class ColumnDefinition
{
    /** @var string Column name */
    public string $name;

    /** @var string Column type (e.g., 'integer', 'varchar', 'text') */
    public string $type;

    /** @var int|null Length for string types */
    public ?int $length = null;

    /** @var int|null Precision for decimal/float types */
    public ?int $precision = null;

    /** @var int|null Scale for decimal/float types */
    public ?int $scale = null;

    /** @var bool Whether the column allows NULL values */
    public bool $isNullable = false;

    /** @var mixed Default value for the column */
    public mixed $defaultValue = null;

    /** @var bool Whether a default has been explicitly set */
    public bool $hasDefault = false;

    /** @var bool Whether the column is unsigned */
    public bool $isUnsigned = false;

    /** @var bool Whether the column auto-increments */
    public bool $isAutoIncrement = false;

    /** @var bool Whether the column is a primary key */
    public bool $isPrimary = false;

    /** @var bool Whether the column has a unique constraint */
    public bool $isUnique = false;

    /** @var bool Whether the column has an index */
    public bool $isIndex = false;

    /** @var string|null Column comment */
    public ?string $comment = null;

    /** @var string|null Column to place this one after */
    public ?string $afterColumn = null;

    /** @var bool Whether to place this column first */
    public bool $isFirst = false;

    /** @var string|null Character set for the column */
    public ?string $charset = null;

    /** @var string|null Collation for the column */
    public ?string $collation = null;

    /** @var bool Use CURRENT_TIMESTAMP as default */
    public bool $useCurrent = false;

    /** @var bool Use CURRENT_TIMESTAMP on update */
    public bool $useCurrentOnUpdate = false;

    /** @var array<string>|null Allowed values for ENUM type */
    public ?array $allowed = null;

    /**
     * @param string $name Column name
     * @param string $type Column type
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Mark the column as nullable.
     *
     * @return static
     */
    public function nullable(): static
    {
        $this->isNullable = true;

        return $this;
    }

    /**
     * Set the default value for the column.
     *
     * @param mixed $value Default value
     * @return static
     */
    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        $this->hasDefault = true;

        return $this;
    }

    /**
     * Mark the column as unsigned.
     *
     * @return static
     */
    public function unsigned(): static
    {
        $this->isUnsigned = true;

        return $this;
    }

    /**
     * Mark the column as auto-incrementing.
     *
     * @return static
     */
    public function autoIncrement(): static
    {
        $this->isAutoIncrement = true;

        return $this;
    }

    /**
     * Mark the column as a primary key.
     *
     * @return static
     */
    public function primary(): static
    {
        $this->isPrimary = true;

        return $this;
    }

    /**
     * Add a unique constraint to the column.
     *
     * @return static
     */
    public function unique(): static
    {
        $this->isUnique = true;

        return $this;
    }

    /**
     * Add an index to the column.
     *
     * @return static
     */
    public function index(): static
    {
        $this->isIndex = true;

        return $this;
    }

    /**
     * Set a comment for the column.
     *
     * @param string $comment The comment text
     * @return static
     */
    public function comment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Place the column after another column (MySQL only).
     *
     * @param string $column The column to place this one after
     * @return static
     */
    public function after(string $column): static
    {
        $this->afterColumn = $column;

        return $this;
    }

    /**
     * Place the column first in the table (MySQL only).
     *
     * @return static
     */
    public function first(): static
    {
        $this->isFirst = true;

        return $this;
    }

    /**
     * Set the character set for the column.
     *
     * @param string $charset Character set name
     * @return static
     */
    public function charset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Set the collation for the column.
     *
     * @param string $collation Collation name
     * @return static
     */
    public function collation(string $collation): static
    {
        $this->collation = $collation;

        return $this;
    }

    /**
     * Use CURRENT_TIMESTAMP as the default value for datetime/timestamp columns.
     *
     * @return static
     */
    public function useCurrent(): static
    {
        $this->useCurrent = true;

        return $this;
    }

    /**
     * Use CURRENT_TIMESTAMP on update for datetime/timestamp columns (MySQL).
     *
     * @return static
     */
    public function useCurrentOnUpdate(): static
    {
        $this->useCurrentOnUpdate = true;

        return $this;
    }
}
