<?php

declare(strict_types=1);

/**
 * 3ymen Framework - HTTP Entry Point
 *
 * All HTTP requests are routed through this file via the web server.
 * This bootstraps the application, handles the request through the HTTP
 * kernel, sends the response, and terminates.
 */

define('APP_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(\Eymen\Foundation\Kernel::class);

$request = \Eymen\Http\Request::fromGlobals();

$response = $kernel->handle($request);

$kernel->sendResponse($response);

$kernel->terminate($request, $response);
