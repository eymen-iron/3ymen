<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;
use Eymen\Http\Router;

/**
 * Route list command.
 *
 * Displays all registered routes in a formatted ASCII table,
 * including method, URI pattern, name, action, and middleware.
 */
class RouteListCommand extends Command
{
    protected string $name = 'route:list';

    protected string $description = 'List all registered routes';

    protected array $options = [
        'method' => [
            'description' => 'Filter by HTTP method (GET, POST, etc.)',
        ],
        'name' => [
            'description' => 'Filter by route name (partial match)',
        ],
        'path' => [
            'description' => 'Filter by URI path (partial match)',
        ],
    ];

    public function handle(): int
    {
        // Load route files to populate the Router
        $this->loadRoutes();

        $routes = Router::getRoutes();

        if ($routes === []) {
            $this->warn('No routes have been registered.');
            return 0;
        }

        // Apply filters
        $filterMethod = $this->option('method');
        $filterName = $this->option('name');
        $filterPath = $this->option('path');

        $rows = [];

        foreach ($routes as $route) {
            // Filter by method
            if ($filterMethod !== null && is_string($filterMethod)) {
                if (strtoupper($filterMethod) !== $route->getMethod()) {
                    continue;
                }
            }

            // Filter by name
            $routeName = $route->getName() ?? '';
            if ($filterName !== null && is_string($filterName)) {
                if (!str_contains($routeName, $filterName)) {
                    continue;
                }
            }

            // Filter by path
            if ($filterPath !== null && is_string($filterPath)) {
                if (!str_contains($route->getPattern(), $filterPath)) {
                    continue;
                }
            }

            $action = $this->formatAction($route->getAction());
            $middleware = implode(', ', $route->getMiddleware());

            // Color the method
            $method = $this->colorMethod($route->getMethod());

            $rows[] = [
                $method,
                $route->getPattern(),
                $routeName,
                $action,
                $middleware,
            ];
        }

        if ($rows === []) {
            $this->warn('No routes matched the given filters.');
            return 0;
        }

        $this->newLine();
        $this->table(
            ['Method', 'URI', 'Name', 'Action', 'Middleware'],
            $rows,
        );
        $this->newLine();

        $this->info("  Showing " . count($rows) . " route(s).");
        $this->newLine();

        return 0;
    }

    /**
     * Load route files to populate the Router.
     */
    private function loadRoutes(): void
    {
        try {
            $app = \Eymen\Foundation\Application::getInstance();
            $routesPath = $app->basePath('routes');

            $webRoutes = $routesPath . '/web.php';
            if (is_file($webRoutes)) {
                require_once $webRoutes;
            }

            $apiRoutes = $routesPath . '/api.php';
            if (is_file($apiRoutes)) {
                require_once $apiRoutes;
            }

            Router::indexNamedRoutes();
        } catch (\RuntimeException) {
            // Application not available; routes may already be loaded
        }
    }

    /**
     * Format a route action for display.
     *
     * @param array|callable $action
     */
    private function formatAction(array|callable $action): string
    {
        if (is_array($action) && count($action) === 2) {
            $class = is_string($action[0]) ? $action[0] : get_class($action[0]);
            $method = $action[1];

            // Shorten class name for readability
            $parts = explode('\\', $class);
            $shortClass = end($parts);

            return "{$shortClass}@{$method}";
        }

        if ($action instanceof \Closure) {
            return 'Closure';
        }

        if (is_callable($action)) {
            return 'Callable';
        }

        return 'Unknown';
    }

    /**
     * Colorize the HTTP method name.
     */
    private function colorMethod(string $method): string
    {
        return match ($method) {
            'GET' => "\033[32mGET\033[0m    ",
            'POST' => "\033[33mPOST\033[0m   ",
            'PUT' => "\033[34mPUT\033[0m    ",
            'PATCH' => "\033[34mPATCH\033[0m  ",
            'DELETE' => "\033[31mDELETE\033[0m ",
            'ANY' => "\033[36mANY\033[0m    ",
            default => $method,
        };
    }
}
