<?php

declare(strict_types=1);

namespace Eymen\Database\Relations;

use Eymen\Database\Model;
use Eymen\Database\QueryBuilder;

/**
 * Base relationship class.
 *
 * Provides shared functionality for all relationship types including
 * query building, filtering, and result hydration.
 */
abstract class Relation
{
    protected Model $parent;
    protected string $related;
    protected string $foreignKey;
    protected string $localKey;
    protected QueryBuilder $query;

    /**
     * @param Model $parent The parent model instance
     * @param string $related The related model class name
     * @param string $foreignKey The foreign key column
     * @param string $localKey The local key column
     */
    public function __construct(Model $parent, string $related, string $foreignKey, string $localKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        /** @var Model $relatedInstance */
        $relatedInstance = new $related();
        $this->query = $related::query();
    }

    /**
     * Execute the relationship query and return results.
     *
     * @return mixed Results vary by relationship type
     */
    abstract public function get(): mixed;

    /**
     * Add a WHERE clause to the relationship query.
     *
     * @param string $column Column name
     * @param mixed $operator Comparison operator or value
     * @param mixed $value Value to compare
     * @return static
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        $this->query->where($column, $operator, $value);

        return $this;
    }

    /**
     * Add an ORDER BY clause to the relationship query.
     *
     * @param string $column Column to order by
     * @param string $direction Sort direction
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query->orderBy($column, $direction);

        return $this;
    }

    /**
     * Set a LIMIT on the relationship query.
     *
     * @param int $limit Maximum number of results
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->query->limit($limit);

        return $this;
    }

    /**
     * Get the parent model's local key value.
     */
    protected function getParentKeyValue(): mixed
    {
        return $this->parent->getAttribute($this->localKey);
    }

    /**
     * Hydrate a row into a model instance.
     *
     * @param array<string, mixed> $attributes Row attributes
     * @return Model The hydrated model
     */
    protected function hydrate(array $attributes): Model
    {
        /** @var Model $instance */
        $instance = new ($this->related)($attributes);
        $instance->syncOriginal();

        return $instance;
    }
}
