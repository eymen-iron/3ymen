<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

final class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';
    protected array $arguments = ['name' => ['description' => 'Controller name', 'required' => true]];
    protected array $options = ['resource' => ['description' => 'Generate resource controller']];

    public function handle(): int
    {
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Controller name is required.');
            return 1;
        }

        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $app = \Eymen\Foundation\Application::getInstance();
        $path = $app->basePath("app/Controllers/{$name}.php");

        if (file_exists($path)) {
            $this->error("Controller already exists: {$path}");
            return 1;
        }

        $resource = $this->hasOption('resource');

        $stub = $this->generateStub($name, $resource);

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Controller created: app/Controllers/{$name}.php");

        return 0;
    }

    private function generateStub(string $name, bool $resource): string
    {
        $methods = '';

        if ($resource) {
            $methods = <<<'PHP'

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        //
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        //
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        //
    }

    public function show(ServerRequestInterface $request, int $id): ResponseInterface
    {
        //
    }

    public function edit(ServerRequestInterface $request, int $id): ResponseInterface
    {
        //
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        //
    }

    public function destroy(ServerRequestInterface $request, int $id): ResponseInterface
    {
        //
    }
PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use Eymen\Http\Psr\ServerRequestInterface;
use Eymen\Http\Psr\ResponseInterface;

final class {$name}
{
{$methods}
}
PHP;
    }
}
