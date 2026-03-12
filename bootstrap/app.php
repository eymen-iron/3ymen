<?php

declare(strict_types=1);

/**
 * 3ymen Framework - Application Bootstrap
 *
 * Creates and configures the application instance, registers the HTTP kernel
 * and console application as singletons. This file is included by both
 * the HTTP entry point (public/index.php) and the CLI binary (3ymen).
 */

$app = new \Eymen\Foundation\Application(
    basePath: dirname(__DIR__)
);

// Register the HTTP kernel as a singleton
$app->singleton(\Eymen\Foundation\Kernel::class, function ($app) {
    return new \Eymen\Foundation\Kernel($app);
});

// Register the console application as a singleton
$app->singleton(\Eymen\Console\Application::class, function ($app) {
    return new \Eymen\Console\Application($app);
});

return $app;
