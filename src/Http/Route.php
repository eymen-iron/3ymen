<?php

declare(strict_types=1);

namespace Eymen\Http;

/**
 * Represents a single registered route.
 */
final class Route
{
    private ?string $name = null;

    /** @var list<string> */
    private array $middleware = [];

    /** @var array|\Closure The route action */
    private readonly array|\Closure $action;

    /**
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $pattern URI pattern with optional parameters
     * @param array|\Closure $action Controller action [Class::class, 'method'] or Closure
     * @param array<string, string> $constraints Parameter constraints from pattern
     */
    public function __construct(
        private readonly string $method,
        private readonly string $pattern,
        array|\Closure $action,
        private array $constraints = [],
    ) {
        $this->action = $action;
    }

    /**
     * Set the route name.
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Add middleware to this route.
     */
    public function middleware(string ...$middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array|\Closure
     */
    public function getAction(): array|\Closure
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return array<string, string>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Match this route against a given URI path and extract parameters.
     *
     * @param string $path The URI path to match
     * @return array{matched: bool, params: array<string, string>}
     */
    public function match(string $path): array
    {
        $patternSegments = explode('/', trim($this->pattern, '/'));
        $pathSegments = explode('/', trim($path, '/'));

        // Handle root path
        if ($this->pattern === '/' && $path === '/') {
            return ['matched' => true, 'params' => []];
        }

        if ($this->pattern === '/' || $path === '/') {
            if (trim($this->pattern, '/') === '' && trim($path, '/') === '') {
                return ['matched' => true, 'params' => []];
            }
        }

        if (count($patternSegments) !== count($pathSegments)) {
            // Check for wildcard {path:any} as last segment
            $lastSegment = end($patternSegments);
            if ($lastSegment !== false && preg_match('/^\{(\w+):any\}$/', $lastSegment)) {
                if (count($pathSegments) < count($patternSegments) - 1) {
                    return ['matched' => false, 'params' => []];
                }
            } else {
                return ['matched' => false, 'params' => []];
            }
        }

        $params = [];

        foreach ($patternSegments as $i => $segment) {
            // Parameter segment: {name} or {name:constraint}
            if (preg_match('/^\{(\w+)(?::(\w+))?\}$/', $segment, $matches)) {
                $paramName = $matches[1];
                $constraint = $matches[2] ?? null;

                if ($constraint === 'any') {
                    // Capture remaining path
                    $params[$paramName] = implode('/', array_slice($pathSegments, $i));
                    return ['matched' => true, 'params' => $params];
                }

                if (!isset($pathSegments[$i])) {
                    return ['matched' => false, 'params' => []];
                }

                $value = $pathSegments[$i];

                if (!$this->matchConstraint($value, $constraint)) {
                    return ['matched' => false, 'params' => []];
                }

                $params[$paramName] = $value;
            } elseif (!isset($pathSegments[$i]) || $segment !== $pathSegments[$i]) {
                return ['matched' => false, 'params' => []];
            }
        }

        return ['matched' => true, 'params' => $params];
    }

    /**
     * Validate a parameter value against a constraint.
     */
    private function matchConstraint(string $value, ?string $constraint): bool
    {
        if ($constraint === null) {
            return true;
        }

        return match ($constraint) {
            'int' => ctype_digit($value),
            'alpha' => ctype_alpha($value),
            'alphanum' => ctype_alnum($value),
            'uuid' => (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value),
            'slug' => (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value),
            default => true,
        };
    }
}
