<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;
use Eymen\View\VexEngine;

final class ViewCacheCommand extends Command
{
    protected string $name = 'view:cache';
    protected string $description = 'Compile all Vex templates';

    public function handle(): int
    {
        $app = \Eymen\Foundation\Application::getInstance();

        $viewPath = $app->resourcePath('views');
        $cachePath = $app->storagePath('cache/views');

        if (!is_dir($viewPath)) {
            $this->error("Views directory not found: {$viewPath}");
            return 1;
        }

        $engine = new VexEngine($viewPath, $cachePath);

        $compiled = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'vex') {
                $relative = str_replace($viewPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $name = str_replace([DIRECTORY_SEPARATOR, '.vex'], ['/', ''], $relative);

                try {
                    $engine->render($name);
                    $this->info("Compiled: {$relative}");
                    $compiled++;
                } catch (\Throwable $e) {
                    $this->warn("Failed: {$relative} - {$e->getMessage()}");
                }
            }
        }

        $this->info("Compiled {$compiled} Vex templates.");

        return 0;
    }
}
