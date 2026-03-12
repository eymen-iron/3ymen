<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-17 server request factory interface.
 */
interface ServerRequestFactoryInterface
{
    /**
     * Create a new server request.
     *
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, string|UriInterface $uri, array $serverParams = []): ServerRequestInterface;
}
