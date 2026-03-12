<?php

declare(strict_types=1);

namespace Eymen\Support;

/**
 * Fluent collection class wrapping arrays.
 *
 * Provides a rich, chainable API for working with arrays of data.
 * Implements standard PHP interfaces for seamless integration.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class Collection implements \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    /**
     * @param array<TKey, TValue> $items The underlying items
     */
    public function __construct(
        private array $items = []
    ) {
    }

    // ─── Static Constructors ───────────────────────────────────────────

    /**
     * Create a new collection instance.
     *
     * @param array<mixed> $items
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    /**
     * Wrap the given value in a collection if applicable.
     */
    public static function wrap(mixed $value): self
    {
        if ($value instanceof self) {
            return new self($value->all());
        }

        return new self(Arr::wrap($value));
    }

    /**
     * Create a new collection by invoking the callback a given number of times.
     */
    public static function times(int $n, callable $fn): self
    {
        if ($n < 1) {
            return new self();
        }

        $items = [];

        for ($i = 1; $i <= $n; $i++) {
            $items[] = $fn($i);
        }

        return new self($items);
    }

    // ─── Transformation Methods ────────────────────────────────────────

    /**
     * Run a map over each of the items.
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $values = array_map($callback, $this->items, $keys);

        return new self(array_combine($keys, $values));
    }

    /**
     * Run a filter over each of the items.
     */
    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }

        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Reduce the collection to a single value.
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Execute a callback over each item.
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Get the first item from the collection passing the given truth test.
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Get the last item from the collection passing the given truth test.
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Get the values of a given key.
     */
    public function pluck(string $value, ?string $key = null): self
    {
        return new self(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key The key to compare
     * @param mixed $operator Comparison operator or value
     * @param mixed $value Comparison value (when operator is provided)
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function (mixed $item) use ($key, $operator, $value): bool {
            $retrieved = data_get($item, $key);

            return match ($operator) {
                '=', '==' => $retrieved == $value,
                '===' => $retrieved === $value,
                '!=' , '<>' => $retrieved != $value,
                '!==' => $retrieved !== $value,
                '<' => $retrieved < $value,
                '<=' => $retrieved <= $value,
                '>' => $retrieved > $value,
                '>=' => $retrieved >= $value,
                default => $retrieved == $value,
            };
        });
    }

    /**
     * Sort the collection by the given callback or key.
     */
    public function sortBy(callable|string $callback, bool $descending = false): self
    {
        return new self(Arr::sortBy($this->items, $callback, $descending));
    }

    /**
     * Group items by a field or callback.
     */
    public function groupBy(callable|string $groupBy): self
    {
        $results = Arr::groupBy($this->items, $groupBy);

        return new self(array_map(fn(array $group): self => new self($group), $results));
    }

    /**
     * Key items by a field or callback.
     */
    public function keyBy(callable|string $keyBy): self
    {
        return new self(Arr::keyBy($this->items, $keyBy));
    }

    /**
     * Return unique items.
     */
    public function unique(callable|string|null $key = null): self
    {
        return new self(Arr::unique($this->items, $key));
    }

    /**
     * Flatten the collection into a single dimension.
     */
    public function flatten(int $depth = PHP_INT_MAX): self
    {
        return new self(Arr::flatten($this->items, $depth));
    }

    /**
     * Reset the keys on the underlying array.
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * Get all keys of the collection items.
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    // ─── Aggregate Methods ─────────────────────────────────────────────

    /**
     * Count the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Determine if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Determine if the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the sum of the given values.
     */
    public function sum(callable|string|null $callback = null): int|float
    {
        $callback = $this->valueRetriever($callback);

        return $this->reduce(function (int|float $result, mixed $item) use ($callback): int|float {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Get the average value of a given key.
     */
    public function avg(callable|string|null $callback = null): int|float|null
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        return $this->sum($callback) / $count;
    }

    /**
     * Get the minimum value of a given key.
     */
    public function min(callable|string|null $callback = null): mixed
    {
        $callback = $this->valueRetriever($callback);

        return $this->map(fn(mixed $value): mixed => $callback($value))
            ->filter(fn(mixed $value): bool => $value !== null)
            ->reduce(fn(mixed $result, mixed $value): mixed => $result === null || $value < $result ? $value : $result);
    }

    /**
     * Get the maximum value of a given key.
     */
    public function max(callable|string|null $callback = null): mixed
    {
        $callback = $this->valueRetriever($callback);

        return $this->map(fn(mixed $value): mixed => $callback($value))
            ->filter(fn(mixed $value): bool => $value !== null)
            ->reduce(fn(mixed $result, mixed $value): mixed => $result === null || $value > $result ? $value : $result);
    }

    /**
     * Get the median of a given key.
     */
    public function median(callable|string|null $callback = null): int|float|null
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        $callback = $this->valueRetriever($callback);

        $values = $this->map(fn(mixed $value): mixed => $callback($value))
            ->filter(fn(mixed $value): bool => $value !== null)
            ->sortBy(fn(mixed $value): mixed => $value)
            ->values();

        $count = $values->count();

        if ($count === 0) {
            return null;
        }

        $middle = (int) ($count / 2);

        if ($count % 2 === 0) {
            return ($values->all()[$middle - 1] + $values->all()[$middle]) / 2;
        }

        return $values->all()[$middle];
    }

    // ─── String Operations ─────────────────────────────────────────────

    /**
     * Join all items using a string.
     */
    public function implode(callable|string $value, ?string $glue = null): string
    {
        if (is_callable($value)) {
            return implode($glue ?? '', $this->map($value)->all());
        }

        $first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue ?? '', $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    // ─── Subset & Manipulation ─────────────────────────────────────────

    /**
     * Slice the underlying collection array.
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Chunk the collection into chunks of the given size.
     */
    public function chunk(int $size): self
    {
        if ($size <= 0) {
            return new self();
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * Take the first or last {limit} items.
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Skip the first {count} items.
     */
    public function skip(int $count): self
    {
        return $this->slice($count);
    }

    /**
     * Determine if an item exists in the collection.
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if (is_callable($key)) {
                foreach ($this->items as $k => $item) {
                    if ($key($item, $k)) {
                        return true;
                    }
                }

                return false;
            }

            return in_array($key, $this->items, false);
        }

        return $this->where(...func_get_args())->isNotEmpty();
    }

    /**
     * Merge the collection with the given items.
     */
    public function merge(mixed $items): self
    {
        if ($items instanceof self) {
            $items = $items->all();
        }

        return new self(array_merge($this->items, (array) $items));
    }

    /**
     * Push one or more items onto the end of the collection.
     */
    public function push(mixed ...$values): self
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Get and remove an item from the collection by key.
     */
    public function pull(string|int $key, mixed $default = null): mixed
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Get and remove the last item from the collection.
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Get and remove the first item from the collection.
     */
    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    /**
     * Reverse items order.
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items, true));
    }

    /**
     * Search the collection for a given value and return the corresponding key.
     */
    public function search(mixed $value, bool $strict = false): int|string|false
    {
        if (is_callable($value)) {
            foreach ($this->items as $key => $item) {
                if ($value($item, $key)) {
                    return $key;
                }
            }

            return false;
        }

        return array_search($value, $this->items, $strict);
    }

    // ─── Higher-Order Methods ──────────────────────────────────────────

    /**
     * Pass the collection to the given callback and return the result.
     */
    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Pass the collection to the given callback and then return it.
     */
    public function tap(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    /**
     * Apply the callback if the given "value" is truthy.
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): self
    {
        $value = is_callable($value) ? $value($this) : $value;

        if ($value) {
            return $callback($this, $value);
        }

        if ($default !== null) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is falsy.
     */
    public function unless(mixed $value, callable $callback, ?callable $default = null): self
    {
        $value = is_callable($value) ? $value($this) : $value;

        if (!$value) {
            return $callback($this, $value);
        }

        if ($default !== null) {
            return $default($this, $value);
        }

        return $this;
    }

    // ─── Set Operations ────────────────────────────────────────────────

    /**
     * Get the items in the collection that are not present in the given items.
     */
    public function diff(mixed $items): self
    {
        if ($items instanceof self) {
            $items = $items->all();
        }

        return new self(array_diff($this->items, (array) $items));
    }

    /**
     * Intersect the collection with the given items.
     */
    public function intersect(mixed $items): self
    {
        if ($items instanceof self) {
            $items = $items->all();
        }

        return new self(array_intersect($this->items, (array) $items));
    }

    /**
     * Flip the items in the collection.
     */
    public function flip(): self
    {
        return new self(array_flip($this->items));
    }

    /**
     * Create a collection by using this collection for keys and another for values.
     */
    public function combine(mixed $values): self
    {
        if ($values instanceof self) {
            $values = $values->all();
        }

        return new self(array_combine($this->items, (array) $values));
    }

    // ─── Output Methods ────────────────────────────────────────────────

    /**
     * Get all items in the collection.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the collection of items as an array.
     *
     * @return array<TKey, mixed>
     */
    public function toArray(): array
    {
        return array_map(function (mixed $value): mixed {
            if ($value instanceof self) {
                return $value->toArray();
            }

            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Get the collection as JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
    }

    // ─── Interface Implementations ─────────────────────────────────────

    /**
     * @return \ArrayIterator<TKey, TValue>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @return array<TKey, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    /**
     * Get a value retriever callback.
     */
    private function valueRetriever(callable|string|null $value): callable
    {
        if (is_callable($value)) {
            return $value;
        }

        if (is_string($value)) {
            return fn(mixed $item): mixed => data_get($item, $value);
        }

        return fn(mixed $item): mixed => $item;
    }
}
