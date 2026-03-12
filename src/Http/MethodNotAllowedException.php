<?php

declare(strict_types=1);

namespace Eymen\Http;

/**
 * Thrown when the URI matches a route but the HTTP method is not allowed.
 *
 * Indicates a 405 Method Not Allowed condition.
 * The allowed methods are available for building the Allow response header.
 */
final class MethodNotAllowedException extends \RuntimeException
{
    private string $method;

    private string $uri;

    /** @var string[] */
    private array $allowedMethods;

    /**
     * @param string   $method         The attempted HTTP method
     * @param string   $uri            The requested URI
     * @param string[] $allowedMethods Methods that are allowed for this URI
     */
    public function __construct(string $method, string $uri, array $allowedMethods)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->allowedMethods = $allowedMethods;

        $allowed = implode(', ', $allowedMethods);

        parent::__construct(
            "Method [{$method}] is not allowed for [{$uri}]. Allowed: [{$allowed}]."
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
