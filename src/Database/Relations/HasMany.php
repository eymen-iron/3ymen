<?php

declare(strict_types=1);

namespace Eymen\Database\Relations;

use Eymen\Database\Model;

/**
 * Has-many relationship.
 *
 * Represents a one-to-many relationship where multiple related models
 * contain a foreign key referencing the parent model.
 *
 * Example: A User hasMany Posts (posts.user_id -> users.id)
 */
final class HasMany extends Relation
{
    /**
     * Execute the query and return all related models.
     *
     * @return array<int, Model> Array of related model instances
     */
    public function get(): array
    {
        $parentKey = $this->getParentKeyValue();

        if ($parentKey === null) {
            return [];
        }

        $results = $this->query
            ->where($this->foreignKey, '=', $parentKey)
            ->get();

        return array_map(
            fn(array $row): Model => $this->hydrate($row),
            $results
        );
    }

    /**
     * Create a new related model associated with the parent.
     *
     * @param array<string, mixed> $attributes Attributes for the new related model
     * @return Model The created model instance
     */
    public function create(array $attributes): Model
    {
        $attributes[$this->foreignKey] = $this->getParentKeyValue();

        return ($this->related)::create($attributes);
    }
}
