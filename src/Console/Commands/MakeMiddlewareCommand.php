<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

final class MakeMiddlewareCommand extends Command
{
    protected string $name = 'make:middleware';
    protected string $description = 'Create a new middleware class';
    protected array $arguments = ['name' => ['description' => 'Middleware name', 'required' => true]];

    public function handle(): int
    {
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Middleware name is required.');
            return 1;
        }

        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $app = \Eymen\Foundation\Application::getInstance();
        $path = $app->basePath("app/Middleware/{$name}.php");

        if (file_exists($path)) {
            $this->error("Middleware already exists: {$path}");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Middleware;

use Eymen\Http\Middleware\MiddlewareInterface;
use Eymen\Http\Middleware\RequestHandlerInterface;
use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;

final class {$name} implements MiddlewareInterface
{
    public function process(ServerRequestInterface \$request, RequestHandlerInterface \$handler): ResponseInterface
    {
        // Before request processing

        \$response = \$handler->handle(\$request);

        // After request processing

        return \$response;
    }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Middleware created: app/Middleware/{$name}.php");

        return 0;
    }
}
