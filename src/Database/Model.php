<?php

declare(strict_types=1);

namespace Eymen\Database;

abstract class Model implements \JsonSerializable
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected bool $incrementing = true;
    protected string $keyType = 'int';
    protected array $fillable = [];
    protected array $guarded = ['id'];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;

    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    protected array $relations = [];

    private static ?Connection $connection = null;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    public static function getConnection(): Connection
    {
        if (self::$connection === null) {
            throw new \RuntimeException('No database connection set on Model');
        }

        return self::$connection;
    }

    public function getTable(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        $class = (new \ReflectionClass($this))->getShortName();
        $snake = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($class)));

        return $snake . 's';
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        return new QueryBuilder(static::getConnection(), $instance->getTable());
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $row = static::query()->find($id, $instance->primaryKey);

        if ($row === null) {
            return null;
        }

        return $instance->newFromRow($row);
    }

    public static function findOrFail(mixed $id): static
    {
        $result = static::find($id);

        if ($result === null) {
            throw new \RuntimeException(static::class . " not found with ID: {$id}");
        }

        return $result;
    }

    public static function all(array $columns = ['*']): array
    {
        $rows = static::query()->select(...$columns)->get();
        $instance = new static();

        return array_map(fn(array $row) => $instance->newFromRow($row), $rows);
    }

    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();

        return $instance;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function first(): ?static
    {
        $row = static::query()->first();

        if ($row === null) {
            return null;
        }

        return (new static())->newFromRow($row);
    }

    public function save(): bool
    {
        $attributes = $this->getDirtyAttributes();

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');

            if (!$this->exists) {
                $attributes['created_at'] = $now;
            }

            $attributes['updated_at'] = $now;
            $this->attributes['updated_at'] = $now;

            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
        }

        if ($this->exists) {
            if (empty($attributes)) {
                return true;
            }

            $affected = static::query()
                ->where($this->primaryKey, '=', $this->getKey())
                ->update($attributes);

            if ($affected > 0) {
                $this->syncOriginal();
            }

            return $affected > 0;
        }

        $id = static::query()->insertGetId($attributes);

        if ($this->incrementing) {
            $this->attributes[$this->primaryKey] = $this->keyType === 'int' ? (int) $id : $id;
        }

        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    public function update(array $attributes = []): bool
    {
        if (!$this->exists) {
            return false;
        }

        $this->fill($attributes);

        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $deleted = static::query()
            ->where($this->primaryKey, '=', $this->getKey())
            ->delete();

        if ($deleted > 0) {
            $this->exists = false;
        }

        return $deleted > 0;
    }

    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        return static::find($this->getKey());
    }

    public function refresh(): static
    {
        $fresh = $this->fresh();

        if ($fresh === null) {
            throw new \RuntimeException('Model no longer exists');
        }

        $this->attributes = $fresh->attributes;
        $this->original = $fresh->original;
        $this->relations = [];

        return $this;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->guarded, true)) {
            return false;
        }

        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable, true);
        }

        return true;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->getAttribute($name) !== null;
    }

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            $relation = $this->$key();
            if ($relation instanceof Relations\Relation) {
                $this->relations[$key] = $relation->get();
                return $this->relations[$key];
            }
        }

        return null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function isDirty(string ...$attributes): bool
    {
        $dirty = $this->getDirtyAttributes();

        if (empty($attributes)) {
            return !empty($dirty);
        }

        foreach ($attributes as $attr) {
            if (array_key_exists($attr, $dirty)) {
                return true;
            }
        }

        return false;
    }

    public function getOriginal(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? null;
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;

        foreach ($this->hidden as $hidden) {
            unset($attributes[$hidden]);
        }

        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = $this->castAttribute($key, $attributes[$key]);
            }
        }

        foreach ($this->relations as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = array_map(
                    fn($item) => $item instanceof self ? $item->toArray() : $item,
                    $value
                );
            } elseif ($value instanceof self) {
                $attributes[$key] = $value->toArray();
            }
        }

        return $attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }

    // Relationships

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): Relations\HasMany
    {
        $relatedInstance = new $related();
        $foreignKey ??= $this->getForeignKey();
        $localKey ??= $this->primaryKey;

        return new Relations\HasMany(
            static::getConnection(),
            $this,
            $relatedInstance,
            $foreignKey,
            $localKey,
        );
    }

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): Relations\HasOne
    {
        $relatedInstance = new $related();
        $foreignKey ??= $this->getForeignKey();
        $localKey ??= $this->primaryKey;

        return new Relations\HasOne(
            static::getConnection(),
            $this,
            $relatedInstance,
            $foreignKey,
            $localKey,
        );
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): Relations\BelongsTo
    {
        $relatedInstance = new $related();
        $foreignKey ??= $relatedInstance->getForeignKey();
        $ownerKey ??= $relatedInstance->primaryKey;

        return new Relations\BelongsTo(
            static::getConnection(),
            $this,
            $relatedInstance,
            $foreignKey,
            $ownerKey,
        );
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
    ): Relations\BelongsToMany {
        $relatedInstance = new $related();

        if ($table === null) {
            $tables = [$this->getTable(), $relatedInstance->getTable()];
            sort($tables);
            $table = implode('_', array_map(fn($t) => rtrim($t, 's'), $tables));
        }

        $foreignPivotKey ??= $this->getForeignKey();
        $relatedPivotKey ??= $relatedInstance->getForeignKey();

        return new Relations\BelongsToMany(
            static::getConnection(),
            $this,
            $relatedInstance,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
        );
    }

    public function getForeignKey(): string
    {
        $class = (new \ReflectionClass($this))->getShortName();
        $snake = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($class)));

        return $snake . '_id';
    }

    // Casting

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        $type = $this->casts[$key];

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : (array) $value,
            'object' => is_string($value) ? json_decode($value) : (object) $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value : new \DateTimeImmutable($value),
            'timestamp' => is_numeric($value) ? (int) $value : strtotime($value),
            default => $this->castEnum($type, $value),
        };
    }

    private function castEnum(string $type, mixed $value): mixed
    {
        if (!enum_exists($type)) {
            return $value;
        }

        if ($value instanceof \BackedEnum) {
            return $value;
        }

        return $type::from($value);
    }

    // Internals

    protected function newFromRow(array $row): static
    {
        $instance = new static();
        $instance->attributes = $row;
        $instance->original = $row;
        $instance->exists = true;

        return $instance;
    }

    protected function getDirtyAttributes(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }
}
