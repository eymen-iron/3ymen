<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-17 URI factory interface.
 */
interface UriFactoryInterface
{
    /**
     * Create a new URI.
     */
    public function createUri(string $uri = ''): UriInterface;
}
