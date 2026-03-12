<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Represents a foreign key constraint definition.
 *
 * Provides a fluent interface for defining foreign key relationships
 * including referenced table/column and cascade behaviors.
 */
final class ForeignKeyDefinition
{
    /** @var string The local column that holds the foreign key */
    public string $column;

    /** @var string|null The referenced column on the foreign table */
    public ?string $referencedColumn = null;

    /** @var string|null The referenced (foreign) table */
    public ?string $referencedTable = null;

    /** @var string Action on delete (CASCADE, SET NULL, RESTRICT, NO ACTION) */
    public string $onDelete = 'RESTRICT';

    /** @var string Action on update (CASCADE, SET NULL, RESTRICT, NO ACTION) */
    public string $onUpdate = 'RESTRICT';

    /**
     * @param string $column The local column name
     */
    public function __construct(string $column)
    {
        $this->column = $column;
    }

    /**
     * Set the referenced column on the foreign table.
     *
     * @param string $column Referenced column name
     * @return static
     */
    public function references(string $column): static
    {
        $this->referencedColumn = $column;

        return $this;
    }

    /**
     * Set the referenced (foreign) table.
     *
     * @param string $table Referenced table name
     * @return static
     */
    public function on(string $table): static
    {
        $this->referencedTable = $table;

        return $this;
    }

    /**
     * Set the action to perform on delete.
     *
     * @param string $action One of: CASCADE, SET NULL, RESTRICT, NO ACTION
     * @return static
     */
    public function onDelete(string $action): static
    {
        $this->onDelete = strtoupper($action);

        return $this;
    }

    /**
     * Set the action to perform on update.
     *
     * @param string $action One of: CASCADE, SET NULL, RESTRICT, NO ACTION
     * @return static
     */
    public function onUpdate(string $action): static
    {
        $this->onUpdate = strtoupper($action);

        return $this;
    }

    /**
     * Set CASCADE for the on-delete action.
     *
     * @return static
     */
    public function cascadeOnDelete(): static
    {
        $this->onDelete = 'CASCADE';

        return $this;
    }

    /**
     * Set CASCADE for the on-update action.
     *
     * @return static
     */
    public function cascadeOnUpdate(): static
    {
        $this->onUpdate = 'CASCADE';

        return $this;
    }

    /**
     * Set SET NULL for the on-delete action.
     *
     * @return static
     */
    public function nullOnDelete(): static
    {
        $this->onDelete = 'SET NULL';

        return $this;
    }
}
