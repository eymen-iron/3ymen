<?php

declare(strict_types=1);

namespace Eymen\Database\Relations;

use Eymen\Database\Model;

/**
 * Has-one relationship.
 *
 * Represents a one-to-one relationship where the related model
 * contains the foreign key referencing the parent model.
 *
 * Example: A User hasOne Profile (profiles.user_id -> users.id)
 */
final class HasOne extends Relation
{
    /**
     * Execute the query and return the related model, or null.
     *
     * @return Model|null The related model instance
     */
    public function get(): ?Model
    {
        $parentKey = $this->getParentKeyValue();

        if ($parentKey === null) {
            return null;
        }

        $result = $this->query
            ->where($this->foreignKey, '=', $parentKey)
            ->first();

        if ($result === null) {
            return null;
        }

        return $this->hydrate($result);
    }
}
