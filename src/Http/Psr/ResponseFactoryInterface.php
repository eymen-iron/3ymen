<?php

declare(strict_types=1);

namespace Eymen\Http\Psr;

/**
 * PSR-17 HTTP response factory interface.
 */
interface ResponseFactoryInterface
{
    /**
     * Create a new response.
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface;
}
