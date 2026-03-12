<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;

/**
 * Built-in development server command.
 *
 * Starts PHP's built-in web server pointed at the public/ directory.
 * Suitable for local development only; not for production use.
 */
class ServeCommand extends Command
{
    protected string $name = 'serve';

    protected string $description = 'Start the development server';

    protected array $options = [
        'host' => [
            'description' => 'The host address to serve on',
            'default' => '0.0.0.0',
        ],
        'port' => [
            'description' => 'The port to serve on',
            'default' => '8000',
        ],
    ];

    public function handle(): int
    {
        $host = (string) $this->option('host');
        $port = (string) $this->option('port');

        // Validate port
        $portInt = (int) $port;
        if ($portInt < 1 || $portInt > 65535) {
            $this->error("Invalid port: {$port}. Must be between 1 and 65535.");
            return 1;
        }

        // Determine the document root
        $publicPath = $this->getPublicPath();

        if (!is_dir($publicPath)) {
            $this->error("Public directory not found: {$publicPath}");
            return 1;
        }

        $entryPoint = $publicPath . '/index.php';

        if (!is_file($entryPoint)) {
            $this->error("Entry point not found: {$entryPoint}");
            return 1;
        }

        $this->newLine();
        $this->info("  3ymen Development Server");
        $this->newLine();
        $this->line("  Server running on [\033[36mhttp://{$host}:{$port}\033[0m]");
        $this->newLine();
        $this->line("  Press Ctrl+C to stop the server.");
        $this->newLine();

        // Build the PHP built-in server command
        $command = sprintf(
            '%s -S %s:%s -t %s %s',
            PHP_BINARY,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($publicPath),
            escapeshellarg($entryPoint),
        );

        // Execute and pass through output
        passthru($command, $exitCode);

        return $exitCode;
    }

    /**
     * Determine the public directory path.
     */
    private function getPublicPath(): string
    {
        // Try to get from the application instance
        try {
            return \Eymen\Foundation\Application::getInstance()->publicPath();
        } catch (\RuntimeException) {
            // Fallback: relative to current working directory
            return getcwd() . '/public';
        }
    }
}
