<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

final class MakeEventCommand extends Command
{
    protected string $name = 'make:event';
    protected string $description = 'Create a new event class';
    protected array $arguments = ['name' => ['description' => 'Event name', 'required' => true]];

    public function handle(): int
    {
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Event name is required.');
            return 1;
        }

        $app = \Eymen\Foundation\Application::getInstance();
        $path = $app->basePath("app/Events/{$name}.php");

        if (file_exists($path)) {
            $this->error("Event already exists: {$path}");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Events;

use Eymen\Events\EventInterface;

final class {$name} implements EventInterface
{
    public function __construct(
        // Define event properties
    ) {
    }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Event created: app/Events/{$name}.php");

        return 0;
    }
}
