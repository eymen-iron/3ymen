<?php

declare(strict_types=1);

namespace Eymen\Http;

/**
 * Thrown when no route matches the requested URI.
 *
 * Indicates a 404 Not Found condition.
 */
final class RouteNotFoundException extends \RuntimeException
{
    private string $method;

    private string $uri;

    public function __construct(string $method, string $uri)
    {
        $this->method = $method;
        $this->uri = $uri;

        parent::__construct("No route found for [{$method} {$uri}].");
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
