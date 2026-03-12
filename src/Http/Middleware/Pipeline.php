<?php

declare(strict_types=1);

namespace Eymen\Http\Middleware;

use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\ServerRequestInterface;

/**
 * PSR-15 compatible middleware pipeline.
 *
 * Processes a request through a stack of middleware before reaching
 * the final request handler (typically the router dispatcher).
 */
final class Pipeline implements RequestHandlerInterface
{
    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    /** @var RequestHandlerInterface The final handler (e.g., router dispatch) */
    private RequestHandlerInterface $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * Add middleware to the pipeline.
     *
     * Middleware is processed in the order they are added (FIFO).
     */
    public function pipe(MiddlewareInterface $middleware): static
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Process the request through the middleware stack.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middleware === []) {
            return $this->fallbackHandler->handle($request);
        }

        $runner = new PipelineRunner($this->middleware, $this->fallbackHandler);

        return $runner->handle($request);
    }
}

/**
 * Internal pipeline runner that processes middleware in sequence.
 *
 * @internal
 */
final class PipelineRunner implements RequestHandlerInterface
{
    private int $index = 0;

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly array $middleware,
        private readonly RequestHandlerInterface $fallbackHandler,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middleware[$this->index])) {
            return $this->fallbackHandler->handle($request);
        }

        $current = $this->middleware[$this->index];

        // Create a new handler with incremented index for the next middleware
        $next = clone $this;
        $next->index++;

        return $current->process($request, $next);
    }
}
