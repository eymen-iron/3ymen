<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;
use Eymen\Http\Router;

final class RouteCacheCommand extends Command
{
    protected string $name = 'route:cache';
    protected string $description = 'Create a route cache file for faster route registration';

    public function handle(): int
    {
        $app = \Eymen\Foundation\Application::getInstance();
        $cachePath = $app->storagePath('framework/routes.cache.php');

        // Load routes
        $routesPath = $app->basePath('routes/web.php');
        if (file_exists($routesPath)) {
            require $routesPath;
        }

        $apiRoutesPath = $app->basePath('routes/api.php');
        if (file_exists($apiRoutesPath)) {
            require $apiRoutesPath;
        }

        $router = Router::getInstance();
        $cached = $router->cache();

        $dir = dirname($cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($cachePath, '<?php return ' . $cached . ';' . PHP_EOL);

        $this->info('Routes cached successfully.');

        return 0;
    }
}
