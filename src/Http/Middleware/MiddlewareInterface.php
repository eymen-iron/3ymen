<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\ServerRequestInterface;

/**
 * PSR-15 compatible middleware interface.
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface $request The incoming request
     * @param RequestHandlerInterface $handler The next handler in the pipeline
     * @return ResponseInterface The response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
