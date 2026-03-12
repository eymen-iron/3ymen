<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Eymen\Http\Router;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Register web routes for the application. These routes are loaded by the
| HTTP kernel and are available to all HTTP requests.
|
*/

Router::get('/', [HomeController::class, 'index'])->name('home');
