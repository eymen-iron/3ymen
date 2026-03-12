<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\ServerRequestInterface;

/**
 * PSR-15 compatible request handler interface.
 */
interface RequestHandlerInterface
{
    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
