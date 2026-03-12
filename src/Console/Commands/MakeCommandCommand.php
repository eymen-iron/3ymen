<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

final class MakeCommandCommand extends Command
{
    protected string $name = 'make:command';
    protected string $description = 'Create a new console command class';
    protected array $arguments = ['name' => ['description' => 'Command name', 'required' => true]];

    public function handle(): int
    {
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Command name is required.');
            return 1;
        }

        if (!str_ends_with($name, 'Command')) {
            $name .= 'Command';
        }

        $app = \Eymen\Foundation\Application::getInstance();
        $path = $app->basePath("app/Console/Commands/{$name}.php");

        if (file_exists($path)) {
            $this->error("Command already exists: {$path}");
            return 1;
        }

        $commandName = strtolower(preg_replace('/Command$/', '', $name));
        $commandName = strtolower(preg_replace('/[A-Z]/', ':$0', lcfirst($commandName)));

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Eymen\Console\Command;

final class {$name} extends Command
{
    protected string \$name = '{$commandName}';
    protected string \$description = '';

    public function handle(): int
    {
        return 0;
    }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Command created: app/Console/Commands/{$name}.php");

        return 0;
    }
}
