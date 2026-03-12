<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

final class MakeModelCommand extends Command
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model class';
    protected array $arguments = ['name' => ['description' => 'Model name', 'required' => true]];
    protected array $options = ['migration' => ['description' => 'Create migration file']];

    public function handle(): int
    {
        $name = $this->argument('name');

        if ($name === null || $name === '') {
            $this->error('Model name is required.');
            return 1;
        }

        $app = \Eymen\Foundation\Application::getInstance();
        $path = $app->basePath("app/Models/{$name}.php");

        if (file_exists($path)) {
            $this->error("Model already exists: {$path}");
            return 1;
        }

        $table = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name))) . 's';

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use Eymen\Database\Model;

final class {$name} extends Model
{
    protected string \$table = '{$table}';

    protected array \$fillable = [];

    protected array \$casts = [];
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("Model created: app/Models/{$name}.php");

        if ($this->hasOption('migration')) {
            $timestamp = date('Y_m_d_His');
            $migrationName = "create_{$table}_table";
            $migrationPath = $app->databasePath("migrations/{$timestamp}_{$migrationName}.php");

            $migrationStub = $this->generateMigrationStub($table);

            $migDir = dirname($migrationPath);
            if (!is_dir($migDir)) {
                mkdir($migDir, 0755, true);
            }

            file_put_contents($migrationPath, $migrationStub);

            $this->info("Migration created: database/migrations/{$timestamp}_{$migrationName}.php");
        }

        return 0;
    }

    private function generateMigrationStub(string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use Eymen\Database\Migration;
use Eymen\Database\Schema;
use Eymen\Database\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        \$this->schema()->create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema()->dropIfExists('{$table}');
    }
};
PHP;
    }
}
