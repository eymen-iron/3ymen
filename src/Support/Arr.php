<?php

declare(strict_types=1);

namespace Eymen\Support;

/**
 * Array utility class with dot-notation access support.
 */
final class Arr
{
    /**
     * Get a value from a nested array using dot notation.
     *
     * @param array<string, mixed> $array
     * @param string $key Dot-notated key (e.g., "app.name")
     * @param mixed $default Default value if key doesn't exist
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a value in a nested array using dot notation.
     *
     * @param array<string, mixed> $array
     * @param string $key Dot-notated key
     * @param mixed $value Value to set
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }

    /**
     * Check if a key exists in a nested array using dot notation.
     *
     * @param array<string, mixed> $array
     * @param string $key Dot-notated key
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Remove a key from a nested array using dot notation.
     *
     * @param array<string, mixed> $array
     * @param string $key Dot-notated key
     */
    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                unset($current[$segment]);
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }

            $current = &$current[$segment];
        }
    }

    /**
     * Flatten a multi-dimensional array with dot notation keys.
     *
     * @param array<string, mixed> $array
     * @param string $prepend Key prefix
     * @return array<string, mixed>
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && $value !== []) {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get a subset of items from the array.
     *
     * @param array<string, mixed> $array
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Get all items except the specified keys.
     *
     * @param array<string, mixed> $array
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Return the first element passing a given truth test.
     *
     * @param array<int|string, mixed> $array
     * @param callable|null $callback
     * @param mixed $default
     */
    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($array === []) {
                return $default;
            }

            return reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Return the last element passing a given truth test.
     *
     * @param array<int|string, mixed> $array
     * @param callable|null $callback
     * @param mixed $default
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($array === []) {
                return $default;
            }

            return end($array);
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Determine if all items pass the given test.
     *
     * @param array<int|string, mixed> $array
     */
    public static function every(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Recursively sort an array by keys.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public static function sortRecursive(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursive($value);
            }
        }

        ksort($array);

        return $array;
    }

    /**
     * Wrap the given value in an array if it is not already one.
     */
    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param array<int, array<string, mixed>> $array
     * @param string $value
     * @param string|null $key
     * @return array<mixed>
     */
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = data_get($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);
                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Sort the array using the given callback or key.
     */
    public static function sortBy(array $array, callable|string $callback, bool $descending = false): array
    {
        $results = $array;

        if (is_string($callback)) {
            $key = $callback;
            $callback = fn(mixed $item): mixed => data_get($item, $key);
        }

        uasort($results, function (mixed $a, mixed $b) use ($callback, $descending): int {
            $aVal = $callback($a);
            $bVal = $callback($b);

            $result = $aVal <=> $bVal;

            return $descending ? -$result : $result;
        });

        return array_values($results);
    }

    /**
     * Group an array by a key or callback.
     *
     * @return array<string, array<mixed>>
     */
    public static function groupBy(array $array, callable|string $groupBy): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $groupKey = is_callable($groupBy)
                ? $groupBy($value, $key)
                : data_get($value, $groupBy);

            $results[(string) $groupKey][] = $value;
        }

        return $results;
    }

    /**
     * Key an array by a field or callback.
     */
    public static function keyBy(array $array, callable|string $keyBy): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $resolvedKey = is_callable($keyBy)
                ? $keyBy($value, $key)
                : data_get($value, $keyBy);

            $results[$resolvedKey] = $value;
        }

        return $results;
    }

    /**
     * Return unique items from the array.
     */
    public static function unique(array $array, callable|string|null $key = null): array
    {
        if ($key === null) {
            return array_values(array_unique($array, SORT_REGULAR));
        }

        $seen = [];
        $results = [];

        foreach ($array as $item) {
            $value = is_callable($key) ? $key($item) : data_get($item, $key);
            $serialized = serialize($value);

            if (!isset($seen[$serialized])) {
                $seen[$serialized] = true;
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $results = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $results[] = $item;
            } elseif ($depth === 1) {
                $results = array_merge($results, array_values($item));
            } else {
                $results = array_merge($results, self::flatten($item, $depth - 1));
            }
        }

        return $results;
    }

    /**
     * Get a value from the array, and remove it.
     */
    public static function pull(array &$array, string|int $key, mixed $default = null): mixed
    {
        $value = $array[$key] ?? $default;
        unset($array[$key]);

        return $value;
    }
}
