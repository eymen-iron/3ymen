<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Base migration class.
 *
 * Defines the contract for database migrations with up/down methods.
 * Each migration receives a database connection and can use the Schema
 * builder for table operations.
 */
abstract class Migration
{
    protected Connection $connection;

    /**
     * Run the migration (apply changes).
     */
    abstract public function up(): void;

    /**
     * Reverse the migration (undo changes).
     */
    abstract public function down(): void;

    /**
     * Set the database connection for this migration.
     *
     * @param Connection $connection The database connection
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Get a Schema builder instance for the current connection.
     */
    protected function schema(): Schema
    {
        return new Schema($this->connection);
    }
}
