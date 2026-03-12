<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

final class MakeJobCommand extends Command
{
    protected string $name = 'make:job';
    protected string $description = 'Create a new job class';
    protected array $arguments = ['name' => ['description' => 'Job name', 'required' => true]];

    public function handle(): int
    {
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Job name is required.');
            return 1;
        }

        $app = \Eymen\Foundation\Application::getInstance();
        $path = $app->basePath("app/Jobs/{$name}.php");

        if (file_exists($path)) {
            $this->error("Job already exists: {$path}");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Jobs;

use Eymen\Queue\Job;

final class {$name} extends Job
{
    public int \$tries = 3;
    public int \$timeout = 60;

    public function __construct(
        // Define job properties
    ) {
    }

    public function handle(): void
    {
        //
    }

    public function failed(\Throwable \$exception): void
    {
        //
    }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Job created: app/Jobs/{$name}.php");

        return 0;
    }
}
