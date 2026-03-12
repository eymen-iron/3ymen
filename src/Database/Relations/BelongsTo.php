<?php

declare(strict_types=1);

namespace Eymen\Database\Relations;

use Eymen\Database\Connection;
use Eymen\Database\Model;
use Eymen\Database\QueryBuilder;

final class BelongsTo extends Relation
{
    public function __construct(
        Connection $connection,
        Model $parent,
        Model $related,
        private readonly string $foreignKey,
        private readonly string $ownerKey,
    ) {
        parent::__construct($connection, $parent, $related);
    }

    public function get(): ?Model
    {
        $ownerValue = $this->parent->getAttribute($this->foreignKey);

        if ($ownerValue === null) {
            return null;
        }

        $row = $this->query()
            ->where($this->ownerKey, '=', $ownerValue)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->newRelatedInstance($row);
    }

    public function associate(Model $model): Model
    {
        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));

        return $this->parent;
    }

    public function dissociate(): Model
    {
        $this->parent->setAttribute($this->foreignKey, null);

        return $this->parent;
    }

    protected function buildQuery(): QueryBuilder
    {
        return $this->query();
    }
}
