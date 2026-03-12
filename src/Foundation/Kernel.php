<?php

declare(strict_types=1);

namespace Eymen\Foundation;

use Eymen\Http\HttpException;
use Eymen\Http\Middleware\MiddlewareInterface;
use Eymen\Http\Middleware\Pipeline;
use Eymen\Http\Middleware\RequestHandlerInterface;
use Eymen\Http\Psr\ResponseInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Response;
use Eymen\Http\Route;
use Eymen\Http\Router;
use Eymen\Http\Stream\StringStream;

/**
 * HTTP Kernel.
 *
 * Handles the full request-to-response lifecycle:
 * 1. Bootstrap the application
 * 2. Build the middleware pipeline
 * 3. Dispatch through the pipeline to the router
 * 4. Resolve the controller and call the action
 * 5. Return the response
 */
class Kernel
{
    protected Application $app;

    /** @var list<string> Global middleware class names (applied to every request) */
    protected array $middleware = [];

    /**
     * Named middleware groups.
     *
     * @var array<string, list<string>> Group name => list of middleware classes
     */
    protected array $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    /**
     * Route middleware aliases.
     *
     * @var array<string, string> Alias => middleware class
     */
    protected array $routeMiddleware = [];

    /** @var bool Whether the kernel has bootstrapped the application */
    private bool $bootstrapped = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * Bootstraps the application, builds the middleware pipeline, dispatches
     * through it, and returns the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->bootstrap();

            // Build the middleware pipeline with the router as the final handler
            $routerHandler = new class($this) implements RequestHandlerInterface {
                public function __construct(private readonly Kernel $kernel)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->kernel->dispatchToRouter($request);
                }
            };

            $pipeline = new Pipeline($routerHandler);

            // Add global middleware
            foreach ($this->middleware as $middlewareClass) {
                $middleware = $this->resolveMiddleware($middlewareClass);
                if ($middleware !== null) {
                    $pipeline->pipe($middleware);
                }
            }

            return $pipeline->handle($request);
        } catch (HttpException $e) {
            return $this->createErrorResponse($e->getStatusCode(), $e->getMessage());
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Terminate the request/response cycle.
     *
     * Called after the response has been sent to the client.
     * Performs cleanup operations.
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $this->app->terminate();
    }

    /**
     * Send the response to the client.
     *
     * Emits HTTP headers and the response body.
     */
    public function sendResponse(ResponseInterface $response): void
    {
        // Send status line
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        $protocol = $response->getProtocolVersion();

        if (!headers_sent()) {
            header(
                sprintf('HTTP/%s %d %s', $protocol, $statusCode, $reasonPhrase),
                true,
                $statusCode,
            );

            // Send headers
            foreach ($response->getHeaders() as $name => $values) {
                $replace = true;
                foreach ($values as $value) {
                    header("{$name}: {$value}", $replace);
                    $replace = false;
                }
            }
        }

        // Send body
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            $chunk = $body->read(8192);
            if ($chunk !== '') {
                echo $chunk;
            }
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Dispatch the request to the router.
     *
     * Matches the route, resolves route-level middleware, then resolves
     * the controller and calls the action method.
     */
    public function dispatchToRouter(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // Dispatch through router
        $result = Router::dispatch($method, $path);

        if ($result === null) {
            // Check for method not allowed (405) vs not found (404)
            $allowedMethods = Router::getAllowedMethods($path);

            if ($allowedMethods !== []) {
                $response = $this->createErrorResponse(405, 'Method Not Allowed');
                return $response->withHeader('Allow', implode(', ', $allowedMethods));
            }

            return $this->createErrorResponse(404, 'Not Found');
        }

        $route = $result['route'];
        $params = $result['params'];

        // Inject route parameters as request attributes
        foreach ($params as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        // Apply route-specific middleware
        $routeMiddleware = $this->resolveRouteMiddleware($route);

        if ($routeMiddleware !== []) {
            $controllerHandler = new class($this, $route, $params, $request) implements RequestHandlerInterface {
                public function __construct(
                    private readonly Kernel $kernel,
                    private readonly Route $route,
                    private readonly array $params,
                    private readonly ServerRequestInterface $originalRequest,
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->kernel->resolveController(
                        $this->route->getAction(),
                        $this->params,
                        $request,
                    );
                }
            };

            $pipeline = new Pipeline($controllerHandler);

            foreach ($routeMiddleware as $middleware) {
                $pipeline->pipe($middleware);
            }

            return $pipeline->handle($request);
        }

        return $this->resolveController($route->getAction(), $params, $request);
    }

    /**
     * Resolve the controller and call the action method.
     *
     * Supports:
     * - [ControllerClass::class, 'method'] array syntax
     * - Closure/callable actions
     *
     * @param array|callable $action The route action
     * @param array<string, string> $params Route parameters
     * @param ServerRequestInterface $request The current request
     */
    public function resolveController(
        array|callable $action,
        array $params,
        ServerRequestInterface $request,
    ): ResponseInterface {
        // Callable/Closure action
        if (is_callable($action) && !is_array($action)) {
            $result = $this->app->call($action, array_merge(
                ['request' => $request],
                $params,
            ));

            return $this->prepareResponse($result);
        }

        // [Controller::class, 'method'] action
        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;

            // Resolve controller from container (with DI)
            $controller = $this->app->make($controllerClass);

            // Build method parameters using reflection
            $reflection = new \ReflectionMethod($controller, $method);
            $args = $this->resolveActionParameters($reflection, $params, $request);

            $result = $controller->$method(...$args);

            return $this->prepareResponse($result);
        }

        throw new \RuntimeException('Invalid route action format.');
    }

    /**
     * Bootstrap the application.
     *
     * Loads route files and boots all registered service providers.
     */
    protected function bootstrap(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        // Load route files
        $this->loadRoutes();

        // Index named routes
        Router::indexNamedRoutes();

        // Boot all registered providers
        $this->app->boot();

        $this->bootstrapped = true;
    }

    /**
     * Load route definition files.
     */
    protected function loadRoutes(): void
    {
        $routesPath = $this->app->basePath('routes');

        $webRoutes = $routesPath . '/web.php';
        if (is_file($webRoutes)) {
            require $webRoutes;
        }

        $apiRoutes = $routesPath . '/api.php';
        if (is_file($apiRoutes)) {
            require $apiRoutes;
        }
    }

    /**
     * Resolve a middleware class name to an instance.
     */
    private function resolveMiddleware(string $class): ?MiddlewareInterface
    {
        if (!class_exists($class)) {
            return null;
        }

        $instance = $this->app->make($class);

        if ($instance instanceof MiddlewareInterface) {
            return $instance;
        }

        return null;
    }

    /**
     * Resolve route-specific middleware.
     *
     * @return list<MiddlewareInterface>
     */
    private function resolveRouteMiddleware(Route $route): array
    {
        $middlewareClasses = [];

        foreach ($route->getMiddleware() as $name) {
            // Check if it's a group name
            if (isset($this->middlewareGroups[$name])) {
                $middlewareClasses = array_merge($middlewareClasses, $this->middlewareGroups[$name]);
                continue;
            }

            // Check if it's an alias (possibly with parameters: 'throttle:60')
            $parts = explode(':', $name, 2);
            $alias = $parts[0];

            if (isset($this->routeMiddleware[$alias])) {
                $middlewareClasses[] = $this->routeMiddleware[$alias];
                continue;
            }

            // Assume it's a fully-qualified class name
            if (class_exists($name)) {
                $middlewareClasses[] = $name;
            }
        }

        $resolved = [];
        foreach ($middlewareClasses as $class) {
            $middleware = $this->resolveMiddleware($class);
            if ($middleware !== null) {
                $resolved[] = $middleware;
            }
        }

        return $resolved;
    }

    /**
     * Resolve controller action method parameters using reflection.
     *
     * Injects the request, route parameters, and container-resolved dependencies.
     *
     * @param array<string, string> $params Route parameters
     * @return list<mixed>
     */
    private function resolveActionParameters(
        \ReflectionMethod $method,
        array $params,
        ServerRequestInterface $request,
    ): array {
        $args = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Type-hinted ServerRequestInterface
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if (is_a($typeName, ServerRequestInterface::class, true)) {
                    $args[] = $request;
                    continue;
                }

                // Try resolving from container
                try {
                    $args[] = $this->app->make($typeName);
                    continue;
                } catch (\RuntimeException) {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                        continue;
                    }
                }
            }

            // Route parameter by name
            if (isset($params[$name])) {
                $value = $params[$name];

                // Cast to int if type hint suggests it
                if ($type instanceof \ReflectionNamedType && $type->getName() === 'int') {
                    $args[] = (int) $value;
                } else {
                    $args[] = $value;
                }
                continue;
            }

            // Default value
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // Nullable
            if ($type !== null && $type->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Unable to resolve parameter [{$name}] for controller action."
            );
        }

        return $args;
    }

    /**
     * Prepare a controller return value as a ResponseInterface.
     *
     * Handles:
     * - ResponseInterface instances (passed through)
     * - Strings (wrapped in 200 response with text/html)
     * - Arrays (JSON encoded)
     * - null (204 No Content)
     */
    private function prepareResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_string($result)) {
            return new Response(
                statusCode: 200,
                headers: ['Content-Type' => 'text/html; charset=UTF-8'],
                body: new StringStream($result),
            );
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            $json = json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            return new Response(
                statusCode: 200,
                headers: ['Content-Type' => 'application/json'],
                body: new StringStream($json),
            );
        }

        if ($result === null) {
            return new Response(statusCode: 204);
        }

        throw new \RuntimeException(
            'Controller action must return ResponseInterface, string, array, or null. Got: ' . get_debug_type($result)
        );
    }

    /**
     * Create a simple error response.
     */
    private function createErrorResponse(int $statusCode, string $message): ResponseInterface
    {
        $isDebug = $this->app->isDebug();

        if ($this->wantsJson()) {
            $body = json_encode([
                'error' => true,
                'status' => $statusCode,
                'message' => $message,
            ], JSON_THROW_ON_ERROR);

            return new Response(
                statusCode: $statusCode,
                headers: ['Content-Type' => 'application/json'],
                body: new StringStream($body),
            );
        }

        $html = $this->renderErrorPage($statusCode, $message, $isDebug);

        return new Response(
            statusCode: $statusCode,
            headers: ['Content-Type' => 'text/html; charset=UTF-8'],
            body: new StringStream($html),
        );
    }

    /**
     * Handle an uncaught exception.
     */
    private function handleException(\Throwable $e): ResponseInterface
    {
        $isDebug = $this->app->isDebug();
        $statusCode = 500;
        $message = $isDebug ? $e->getMessage() : 'Internal Server Error';

        if ($isDebug) {
            $detail = sprintf(
                "%s: %s\nin %s:%d\n\nStack trace:\n%s",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString(),
            );

            $html = '<html><head><title>500 - Internal Server Error</title>'
                . '<style>body{font-family:monospace;padding:2rem;background:#1a1a2e;color:#e0e0e0}'
                . 'h1{color:#ff6b6b}pre{background:#16213e;padding:1.5rem;border-radius:8px;overflow:auto;border-left:4px solid #ff6b6b}'
                . '</style></head><body>'
                . '<h1>500 Internal Server Error</h1>'
                . '<pre>' . htmlspecialchars($detail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>'
                . '</body></html>';

            return new Response(
                statusCode: $statusCode,
                headers: ['Content-Type' => 'text/html; charset=UTF-8'],
                body: new StringStream($html),
            );
        }

        return $this->createErrorResponse($statusCode, $message);
    }

    /**
     * Render a simple error page.
     */
    private function renderErrorPage(int $statusCode, string $message, bool $debug): string
    {
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$statusCode} - {$safeMessage}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8f9fa; color: #333; }
                .error { text-align: center; padding: 2rem; }
                .code { font-size: 6rem; font-weight: 700; color: #dee2e6; margin: 0; line-height: 1; }
                .message { font-size: 1.25rem; color: #6c757d; margin-top: 1rem; }
            </style>
        </head>
        <body>
            <div class="error">
                <p class="code">{$statusCode}</p>
                <p class="message">{$safeMessage}</p>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Determine if the current request expects a JSON response.
     */
    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || str_contains($accept, '+json');
    }
}
