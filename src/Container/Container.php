<?php

declare(strict_types=1);

namespace Eymen\Container;

/**
 * PSR-11 compatible Dependency Injection Container with auto-wiring.
 *
 * Supports singleton/transient bindings, contextual binding,
 * automatic constructor injection via reflection, and lazy service resolution.
 */
class Container
{
    /** @var array<string, callable> Factory bindings */
    protected array $bindings = [];

    /** @var array<string, bool> Singleton markers */
    protected array $singletons = [];

    /** @var array<string, mixed> Resolved singleton instances */
    protected array $resolved = [];

    /** @var array<string, array<string, string>> Contextual bindings */
    protected array $contextual = [];

    /** @var array<string, \ReflectionClass<object>> Reflection cache */
    private array $reflectionCache = [];

    /** @var list<string> Resolution stack for circular dependency detection */
    private array $buildStack = [];

    /**
     * Register a binding in the container.
     *
     * @param string $abstract The abstract type or interface
     * @param callable|string|null $concrete The factory, class name, or null (use abstract as concrete)
     */
    public function bind(string $abstract, callable|string|null $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (is_string($concrete)) {
            $class = $concrete;
            $concrete = fn() => $this->build($class);
        }

        $this->bindings[$abstract] = $concrete;
        unset($this->singletons[$abstract], $this->resolved[$abstract]);
    }

    /**
     * Register a shared binding (singleton).
     *
     * @param string $abstract The abstract type or interface
     * @param callable|string|null $concrete The factory, class name, or null
     */
    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->singletons[$abstract] = true;
    }

    /**
     * Register an existing instance as a singleton.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->resolved[$abstract] = $instance;
        $this->singletons[$abstract] = true;
    }

    /**
     * Resolve a type from the container.
     *
     * @throws \RuntimeException If the type cannot be resolved
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * PSR-11: Find an entry of the container by its identifier and return it.
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * PSR-11: Returns true if the container can return an entry for the given identifier.
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id])
            || isset($this->resolved[$id])
            || class_exists($id);
    }

    /**
     * Register a contextual binding.
     *
     * When $concrete needs $abstract, provide $implementation instead.
     */
    public function addContextualBinding(string $concrete, string $abstract, string|callable $implementation): void
    {
        $this->contextual[$concrete][$abstract] = is_string($implementation)
            ? $implementation
            : $implementation;
    }

    /**
     * Start a contextual binding chain.
     */
    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /**
     * Call a method on an object with automatic dependency injection.
     *
     * @param array{0: object|string, 1: string}|callable $callback
     * @param array<string, mixed> $parameters
     */
    public function call(array|callable $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            [$target, $method] = $callback;

            if (is_string($target)) {
                $target = $this->make($target);
            }

            $reflection = new \ReflectionMethod($target, $method);
            $args = $this->resolveMethodDependencies($reflection, $parameters, get_class($target));

            return $target->$method(...$args);
        }

        if ($callback instanceof \Closure) {
            $reflection = new \ReflectionFunction($callback);
            $args = $this->resolveFunctionDependencies($reflection, $parameters);

            return $callback(...$args);
        }

        if (is_string($callback) && function_exists($callback)) {
            $reflection = new \ReflectionFunction($callback);
            $args = $this->resolveFunctionDependencies($reflection, $parameters);

            return $callback(...$args);
        }

        throw new \RuntimeException('Invalid callback provided to Container::call()');
    }

    /**
     * Flush all bindings and resolved instances.
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->singletons = [];
        $this->resolved = [];
        $this->contextual = [];
        $this->reflectionCache = [];
        $this->buildStack = [];
    }

    /**
     * Check if an abstract type is a registered singleton.
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->singletons[$abstract]);
    }

    /**
     * Remove a resolved instance (forcing re-resolution on next access).
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->resolved[$abstract]);
    }

    /**
     * Core resolution logic.
     *
     * @param array<string, mixed> $parameters Override parameters
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        // 1. Already resolved singleton?
        if (isset($this->resolved[$abstract]) && $parameters === []) {
            return $this->resolved[$abstract];
        }

        // 2. Has explicit binding?
        if (isset($this->bindings[$abstract])) {
            $object = ($this->bindings[$abstract])($this, $parameters);

            if (isset($this->singletons[$abstract]) && $parameters === []) {
                $this->resolved[$abstract] = $object;
            }

            return $object;
        }

        // 3. Auto-wire
        return $this->build($abstract, $parameters);
    }

    /**
     * Build a concrete class using reflection and auto-wiring.
     *
     * @param array<string, mixed> $parameters Override parameters
     * @throws \RuntimeException If the class cannot be built
     */
    protected function build(string $concrete, array $parameters = []): object
    {
        // Circular dependency detection
        if (in_array($concrete, $this->buildStack, true)) {
            $chain = implode(' -> ', $this->buildStack) . ' -> ' . $concrete;
            throw new \RuntimeException("Circular dependency detected: {$chain}");
        }

        $this->buildStack[] = $concrete;

        try {
            $reflector = $this->getReflection($concrete);

            if (!$reflector->isInstantiable()) {
                throw new \RuntimeException("Target [{$concrete}] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if ($constructor === null) {
                return $reflector->newInstance();
            }

            $args = $this->resolveMethodDependencies($constructor, $parameters, $concrete);

            return $reflector->newInstanceArgs($args);
        } finally {
            array_pop($this->buildStack);
        }
    }

    /**
     * Resolve method parameter dependencies.
     *
     * @param array<string, mixed> $parameters Override parameters
     * @return list<mixed>
     */
    private function resolveMethodDependencies(
        \ReflectionMethod $method,
        array $parameters,
        string $forClass = '',
    ): array {
        $args = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();

            // 1. Explicit parameter provided?
            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
                continue;
            }

            // 2. Type-hinted class/interface?
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                // Check contextual binding
                if ($forClass !== '' && isset($this->contextual[$forClass][$typeName])) {
                    $contextual = $this->contextual[$forClass][$typeName];
                    if (is_callable($contextual)) {
                        $args[] = $contextual($this);
                    } else {
                        $args[] = $this->make($contextual);
                    }
                    continue;
                }

                try {
                    $args[] = $this->make($typeName);
                    continue;
                } catch (\RuntimeException $e) {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                        continue;
                    }
                    throw $e;
                }
            }

            // 3. Default value?
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // 4. Nullable?
            if ($type !== null && $type->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Unable to resolve parameter [{$name}] in class [{$forClass}]"
            );
        }

        return $args;
    }

    /**
     * Resolve function parameter dependencies.
     *
     * @param array<string, mixed> $parameters Override parameters
     * @return list<mixed>
     */
    private function resolveFunctionDependencies(
        \ReflectionFunction $function,
        array $parameters,
    ): array {
        $args = [];

        foreach ($function->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
                continue;
            }

            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $args[] = $this->make($type->getName());
                    continue;
                } catch (\RuntimeException $e) {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                        continue;
                    }
                    throw $e;
                }
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            if ($type !== null && $type->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Unable to resolve parameter [{$name}] in closure/function"
            );
        }

        return $args;
    }

    /**
     * Get a cached reflection class.
     *
     * @return \ReflectionClass<object>
     */
    private function getReflection(string $class): \ReflectionClass
    {
        if (!isset($this->reflectionCache[$class])) {
            try {
                $this->reflectionCache[$class] = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                throw new \RuntimeException("Target class [{$class}] does not exist.", 0, $e);
            }
        }

        return $this->reflectionCache[$class];
    }
}
