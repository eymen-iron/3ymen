<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-17 HTTP request factory interface.
 */
interface RequestFactoryInterface
{
    /**
     * Create a new request.
     */
    public function createRequest(string $method, string|UriInterface $uri): RequestInterface;
}
