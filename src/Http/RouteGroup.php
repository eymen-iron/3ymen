<?php

declare(strict_types=1);

namespace Eymen\Http;

/**
 * Route group configuration with prefix, middleware, namespace, and name prefix.
 *
 * Groups allow sharing attributes across multiple routes. Groups can be
 * nested, with attributes merging according to specific rules:
 *
 * - prefix: Concatenated with '/' separator
 * - middleware: Merged (outer + inner)
 * - namespace: Concatenated with '\' separator
 * - as (name prefix): Concatenated directly
 *
 * Usage:
 *     Router::group(['prefix' => '/api/v1', 'middleware' => ['auth']], function () {
 *         Router::get('/users', [UserController::class, 'index']);
 *     });
 */
final class RouteGroup
{
    /**
     * @param array{
     *     prefix?: string,
     *     middleware?: string[],
     *     namespace?: string,
     *     as?: string
     * } $attributes Group attributes
     */
    public function __construct(
        private readonly array $attributes = [],
    ) {
    }

    /**
     * Get the URI prefix for routes in this group.
     */
    public function getPrefix(): string
    {
        return $this->attributes['prefix'] ?? '';
    }

    /**
     * Get the middleware stack for routes in this group.
     *
     * @return string[]
     */
    public function getMiddleware(): array
    {
        $middleware = $this->attributes['middleware'] ?? [];

        return is_array($middleware) ? $middleware : [$middleware];
    }

    /**
     * Get the controller namespace for routes in this group.
     */
    public function getNamespace(): ?string
    {
        return $this->attributes['namespace'] ?? null;
    }

    /**
     * Get the name prefix for routes in this group.
     */
    public function getNamePrefix(): string
    {
        return $this->attributes['as'] ?? '';
    }

    /**
     * Get the raw attributes array.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Merge new group attributes with existing (old) attributes.
     *
     * Rules:
     * - prefix:     old prefix + '/' + new prefix (normalized)
     * - middleware:  old middleware + new middleware (appended)
     * - namespace:   old namespace + '\' + new namespace (concatenated)
     * - as:         old name prefix + new name prefix (concatenated)
     *
     * @param array<string, mixed> $new Inner (new) group attributes
     * @param array<string, mixed> $old Outer (old) group attributes
     *
     * @return array<string, mixed> Merged attributes
     */
    public static function merge(array $new, array $old): array
    {
        $merged = $old;

        // Merge prefix: concatenate with path separator
        if (isset($new['prefix'])) {
            $oldPrefix = trim($old['prefix'] ?? '', '/');
            $newPrefix = trim($new['prefix'], '/');

            $merged['prefix'] = $oldPrefix !== ''
                ? '/' . $oldPrefix . '/' . $newPrefix
                : '/' . $newPrefix;
        }

        // Merge middleware: append new to old
        if (isset($new['middleware'])) {
            $oldMiddleware = $old['middleware'] ?? [];
            $newMiddleware = is_array($new['middleware']) ? $new['middleware'] : [$new['middleware']];
            $oldMiddleware = is_array($oldMiddleware) ? $oldMiddleware : [$oldMiddleware];

            $merged['middleware'] = array_merge($oldMiddleware, $newMiddleware);
        }

        // Merge namespace: concatenate with backslash separator
        if (isset($new['namespace'])) {
            $oldNs = rtrim($old['namespace'] ?? '', '\\');
            $newNs = ltrim($new['namespace'], '\\');

            $merged['namespace'] = $oldNs !== ''
                ? $oldNs . '\\' . $newNs
                : $newNs;
        }

        // Merge name prefix: concatenate directly
        if (isset($new['as'])) {
            $merged['as'] = ($old['as'] ?? '') . $new['as'];
        }

        return $merged;
    }
}
