<?php

declare(strict_types=1);

namespace Eymen\Http;

/**
 * HTTP Router with static API.
 *
 * Supports route registration, grouping, parameter constraints, and named routes.
 * Uses a flat route collection with pattern matching (trie optimization in cache layer).
 */
final class Router
{
    /** @var list<Route> All registered routes */
    private static array $routes = [];

    /** @var array<string, Route> Named routes index */
    private static array $namedRoutes = [];

    /** @var list<array{prefix: string, middleware: list<string>}> Active group stack */
    private static array $groupStack = [];

    /**
     * Register a GET route.
     */
    public static function get(string $pattern, array|\Closure $action): Route
    {
        return self::addRoute('GET', $pattern, $action);
    }

    /**
     * Register a POST route.
     */
    public static function post(string $pattern, array|\Closure $action): Route
    {
        return self::addRoute('POST', $pattern, $action);
    }

    /**
     * Register a PUT route.
     */
    public static function put(string $pattern, array|\Closure $action): Route
    {
        return self::addRoute('PUT', $pattern, $action);
    }

    /**
     * Register a PATCH route.
     */
    public static function patch(string $pattern, array|\Closure $action): Route
    {
        return self::addRoute('PATCH', $pattern, $action);
    }

    /**
     * Register a DELETE route.
     */
    public static function delete(string $pattern, array|\Closure $action): Route
    {
        return self::addRoute('DELETE', $pattern, $action);
    }

    /**
     * Register a route for any HTTP method.
     */
    public static function any(string $pattern, array|\Closure $action): Route
    {
        return self::addRoute('ANY', $pattern, $action);
    }

    /**
     * Register a route that responds to multiple HTTP methods.
     *
     * @param list<string> $methods
     */
    public static function match(array $methods, string $pattern, array|\Closure $action): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = self::addRoute(strtoupper($method), $pattern, $action);
        }
        /** @var Route $route */
        return $route;
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array{prefix?: string, middleware?: list<string>} $attributes
     * @param callable $callback
     */
    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = [
            'prefix' => $attributes['prefix'] ?? '',
            'middleware' => $attributes['middleware'] ?? [],
        ];

        $callback();

        array_pop(self::$groupStack);
    }

    /**
     * Register RESTful resource routes.
     *
     * @param string $name Resource name (e.g., 'posts')
     * @param string $controller Controller class name
     */
    public static function resource(string $name, string $controller): void
    {
        self::get("/{$name}", [$controller, 'index'])->name("{$name}.index");
        self::get("/{$name}/create", [$controller, 'create'])->name("{$name}.create");
        self::post("/{$name}", [$controller, 'store'])->name("{$name}.store");
        self::get("/{$name}/{id:int}", [$controller, 'show'])->name("{$name}.show");
        self::get("/{$name}/{id:int}/edit", [$controller, 'edit'])->name("{$name}.edit");
        self::put("/{$name}/{id:int}", [$controller, 'update'])->name("{$name}.update");
        self::delete("/{$name}/{id:int}", [$controller, 'destroy'])->name("{$name}.destroy");
    }

    /**
     * Dispatch a request to a matching route.
     *
     * @param string $method HTTP method
     * @param string $path URI path
     * @return array{route: Route, params: array<string, string>}|null
     */
    public static function dispatch(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');

        foreach (self::$routes as $route) {
            if ($route->getMethod() !== $method && $route->getMethod() !== 'ANY') {
                continue;
            }

            $result = $route->match($path);

            if ($result['matched']) {
                return ['route' => $route, 'params' => $result['params']];
            }
        }

        // Check if any route matches with a different method (for 405)
        foreach (self::$routes as $route) {
            $result = $route->match($path);
            if ($result['matched']) {
                return null; // Indicates 405 - Method Not Allowed
            }
        }

        return null;
    }

    /**
     * Get all registered routes.
     *
     * @return list<Route>
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get a named route.
     */
    public static function getNamedRoute(string $name): ?Route
    {
        return self::$namedRoutes[$name] ?? null;
    }

    /**
     * Generate a URL for a named route.
     *
     * @param string $name Route name
     * @param array<string, string> $params Route parameters
     */
    public static function url(string $name, array $params = []): string
    {
        $route = self::$namedRoutes[$name] ?? null;

        if ($route === null) {
            throw new \RuntimeException("Route [{$name}] not defined.");
        }

        $pattern = $route->getPattern();

        foreach ($params as $key => $value) {
            $pattern = preg_replace('/\{' . $key . '(?::\w+)?\}/', $value, $pattern) ?? $pattern;
        }

        return $pattern;
    }

    /**
     * Clear all registered routes (useful for testing).
     */
    public static function reset(): void
    {
        self::$routes = [];
        self::$namedRoutes = [];
        self::$groupStack = [];
    }

    /**
     * Register a route internally.
     */
    private static function addRoute(string $method, string $pattern, array|\Closure $action): Route
    {
        $prefix = '';
        $middleware = [];

        foreach (self::$groupStack as $group) {
            $prefix .= $group['prefix'];
            $middleware = array_merge($middleware, $group['middleware']);
        }

        $fullPattern = $prefix . $pattern;

        // Normalize pattern (remove double slashes, ensure leading slash)
        $fullPattern = '/' . trim(preg_replace('#/+#', '/', $fullPattern) ?? $fullPattern, '/');

        if ($fullPattern === '') {
            $fullPattern = '/';
        }

        $route = new Route(
            method: $method,
            pattern: $fullPattern,
            action: $action,
        );

        if ($middleware !== []) {
            $route->middleware(...$middleware);
        }

        self::$routes[] = $route;

        // Index named routes after name() is called via the fluent API
        // We use a reference-based approach: when name() is called on the route,
        // we need to update the index. We handle this by scanning on access.
        // However, for immediate indexing, we defer to registerNamed().

        return $route;
    }

    /**
     * Index a route by name. Called during route file loading finalization.
     */
    public static function indexNamedRoutes(): void
    {
        self::$namedRoutes = [];

        foreach (self::$routes as $route) {
            $name = $route->getName();
            if ($name !== null) {
                self::$namedRoutes[$name] = $route;
            }
        }
    }

    /**
     * Check if a path matches any route regardless of method (for 405 detection).
     */
    public static function getAllowedMethods(string $path): array
    {
        $path = '/' . trim($path, '/');
        $methods = [];

        foreach (self::$routes as $route) {
            $result = $route->match($path);
            if ($result['matched']) {
                $methods[] = $route->getMethod();
            }
        }

        return $methods;
    }
}
