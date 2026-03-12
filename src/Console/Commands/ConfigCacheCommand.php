<?php

declare(strict_types=1);

namespace Eymen\Console\Commands;

use Eymen\Console\Command;
use Eymen\Support\Config;

final class ConfigCacheCommand extends Command
{
    protected string $name = 'config:cache';
    protected string $description = 'Create a cache file for faster configuration loading';

    public function handle(): int
    {
        $app = \Eymen\Foundation\Application::getInstance();
        $cachePath = $app->storagePath('framework/config.cache.php');

        // Clear existing cache
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        // Load fresh config
        $config = new Config($app->configPath());
        $configDir = $app->configPath();

        if (is_dir($configDir)) {
            $files = glob($configDir . '/*.php');
            foreach ($files as $file) {
                $key = basename($file, '.php');
                $config->set($key, require $file);
            }
        }

        $config->cache($cachePath);

        $this->info('Configuration cached successfully.');

        return 0;
    }
}
