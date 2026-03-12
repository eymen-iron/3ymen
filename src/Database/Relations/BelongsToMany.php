<?php

declare(strict_types=1);

namespace Eymen\Database\Relations;

use Eymen\Database\Connection;
use Eymen\Database\Model;
use Eymen\Database\QueryBuilder;

final class BelongsToMany extends Relation
{
    public function __construct(
        Connection $connection,
        Model $parent,
        Model $related,
        private readonly string $pivotTable,
        private readonly string $foreignPivotKey,
        private readonly string $relatedPivotKey,
    ) {
        parent::__construct($connection, $parent, $related);
    }

    public function get(): array
    {
        $parentKey = $this->parent->getKey();

        if ($parentKey === null) {
            return [];
        }

        $relatedTable = $this->related->getTable();
        $relatedPrimary = $this->related->getKeyName();

        $rows = $this->query()
            ->select("{$relatedTable}.*")
            ->join(
                $this->pivotTable,
                "{$relatedTable}.{$relatedPrimary}",
                '=',
                "{$this->pivotTable}.{$this->relatedPivotKey}"
            )
            ->where("{$this->pivotTable}.{$this->foreignPivotKey}", '=', $parentKey)
            ->get();

        return array_map(fn(array $row) => $this->newRelatedInstance($row), $rows);
    }

    public function attach(mixed $id, array $attributes = []): bool
    {
        $ids = is_array($id) ? $id : [$id];
        $parentKey = $this->parent->getKey();

        foreach ($ids as $relatedId) {
            $record = array_merge([
                $this->foreignPivotKey => $parentKey,
                $this->relatedPivotKey => $relatedId,
            ], $attributes);

            (new QueryBuilder($this->connection, $this->pivotTable))->insert($record);
        }

        return true;
    }

    public function detach(mixed $id = null): int
    {
        $query = (new QueryBuilder($this->connection, $this->pivotTable))
            ->where($this->foreignPivotKey, '=', $this->parent->getKey());

        if ($id !== null) {
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn($this->relatedPivotKey, $ids);
        }

        return $query->delete();
    }

    public function sync(array $ids): array
    {
        $detached = $this->detach();

        $attached = [];
        foreach ($ids as $id) {
            $this->attach($id);
            $attached[] = $id;
        }

        return ['attached' => $attached, 'detached' => $detached];
    }

    public function toggle(array $ids): array
    {
        $parentKey = $this->parent->getKey();

        $current = (new QueryBuilder($this->connection, $this->pivotTable))
            ->where($this->foreignPivotKey, '=', $parentKey)
            ->pluck($this->relatedPivotKey);

        $attached = [];
        $detached = [];

        foreach ($ids as $id) {
            if (in_array($id, $current)) {
                $this->detach($id);
                $detached[] = $id;
            } else {
                $this->attach($id);
                $attached[] = $id;
            }
        }

        return ['attached' => $attached, 'detached' => $detached];
    }

    protected function buildQuery(): QueryBuilder
    {
        return $this->query();
    }
}
