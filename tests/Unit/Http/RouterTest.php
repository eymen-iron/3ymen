<?php

declare(strict_types=1);

use Eymen\Http\Router;
use Eymen\Http\Route;

beforeEach(function () {
    Router::reset();
});

// Basic routing
test('register and dispatch GET route', function () {
    Router::get('/hello', fn () => 'Hello World');
    $match = Router::dispatch('GET', '/hello');

    expect($match)->not->toBeNull();
    expect($match['route'])->toBeInstanceOf(Route::class);
});

test('register and dispatch POST route', function () {
    Router::post('/users', fn () => 'Create User');
    $match = Router::dispatch('POST', '/users');

    expect($match)->not->toBeNull();
});

test('register PUT route', function () {
    Router::put('/users/1', fn () => 'Update User');
    $match = Router::dispatch('PUT', '/users/1');

    expect($match)->not->toBeNull();
});

test('register DELETE route', function () {
    Router::delete('/users/1', fn () => 'Delete User');
    $match = Router::dispatch('DELETE', '/users/1');

    expect($match)->not->toBeNull();
});

test('register PATCH route', function () {
    Router::patch('/users/1', fn () => 'Patch User');
    $match = Router::dispatch('PATCH', '/users/1');

    expect($match)->not->toBeNull();
});

test('any matches all methods', function () {
    Router::any('/wildcard', fn () => 'Any');

    expect(Router::dispatch('GET', '/wildcard'))->not->toBeNull();
    expect(Router::dispatch('POST', '/wildcard'))->not->toBeNull();
    expect(Router::dispatch('PUT', '/wildcard'))->not->toBeNull();
    expect(Router::dispatch('DELETE', '/wildcard'))->not->toBeNull();
});

test('match registers specific methods', function () {
    Router::match(['GET', 'POST'], '/mixed', fn () => 'Mixed');

    expect(Router::dispatch('GET', '/mixed'))->not->toBeNull();
    expect(Router::dispatch('POST', '/mixed'))->not->toBeNull();
    expect(Router::dispatch('DELETE', '/mixed'))->toBeNull();
});

// Route not found
test('dispatch returns null for unknown route', function () {
    Router::get('/exists', fn () => 'ok');
    expect(Router::dispatch('GET', '/nonexistent'))->toBeNull();
});

test('dispatch returns null for wrong method', function () {
    Router::get('/only-get', fn () => 'ok');
    expect(Router::dispatch('POST', '/only-get'))->toBeNull();
});

// Route parameters
test('route captures named parameters', function () {
    Router::get('/users/{id}', fn () => 'User');
    $match = Router::dispatch('GET', '/users/42');

    expect($match)->not->toBeNull();
    expect($match['params']['id'])->toBe('42');
});

test('route captures multiple parameters', function () {
    Router::get('/posts/{post}/comments/{comment}', fn () => 'Comment');
    $match = Router::dispatch('GET', '/posts/5/comments/10');

    expect($match)->not->toBeNull();
    expect($match['params']['post'])->toBe('5');
    expect($match['params']['comment'])->toBe('10');
});

// Typed constraints
test('route with int constraint matches numbers only', function () {
    Router::get('/users/{id:int}', fn () => 'User');

    expect(Router::dispatch('GET', '/users/42'))->not->toBeNull();
    expect(Router::dispatch('GET', '/users/abc'))->toBeNull();
});

// Named routes
test('named routes can be looked up after indexing', function () {
    Router::get('/profile', fn () => 'Profile')->name('profile');
    Router::indexNamedRoutes();

    $route = Router::getNamedRoute('profile');
    expect($route)->not->toBeNull();
    expect($route->getName())->toBe('profile');
});

test('url generates URL from named route', function () {
    Router::get('/users/{id}', fn () => 'User')->name('user.show');
    Router::indexNamedRoutes();

    $url = Router::url('user.show', ['id' => '42']);
    expect($url)->toBe('/users/42');
});

// Route groups
test('group adds prefix to routes', function () {
    Router::group(['prefix' => '/api'], function () {
        Router::get('/users', fn () => 'API Users');
        Router::get('/posts', fn () => 'API Posts');
    });

    expect(Router::dispatch('GET', '/api/users'))->not->toBeNull();
    expect(Router::dispatch('GET', '/api/posts'))->not->toBeNull();
    expect(Router::dispatch('GET', '/users'))->toBeNull();
});

test('nested groups merge prefixes', function () {
    Router::group(['prefix' => '/api'], function () {
        Router::group(['prefix' => '/v1'], function () {
            Router::get('/users', fn () => 'V1 Users');
        });
    });

    expect(Router::dispatch('GET', '/api/v1/users'))->not->toBeNull();
});

test('group adds middleware to routes', function () {
    Router::group(['middleware' => ['auth']], function () {
        Router::get('/dashboard', fn () => 'Dashboard')->name('dashboard');
    });
    Router::indexNamedRoutes();

    $route = Router::getNamedRoute('dashboard');
    expect($route)->not->toBeNull();
    expect($route->getMiddleware())->toContain('auth');
});

// Resource routes
test('resource registers CRUD routes', function () {
    Router::resource('posts', 'PostController');

    expect(Router::dispatch('GET', '/posts'))->not->toBeNull();          // index
    expect(Router::dispatch('POST', '/posts'))->not->toBeNull();         // store
    expect(Router::dispatch('GET', '/posts/1'))->not->toBeNull();        // show
    expect(Router::dispatch('PUT', '/posts/1'))->not->toBeNull();        // update
    expect(Router::dispatch('DELETE', '/posts/1'))->not->toBeNull();     // destroy
});

// Route collection
test('getRoutes returns all registered routes', function () {
    Router::get('/a', fn () => 'A');
    Router::post('/b', fn () => 'B');
    Router::put('/c', fn () => 'C');

    $routes = Router::getRoutes();
    expect(count($routes))->toBeGreaterThanOrEqual(3);
});

// Route fluent API
test('route name method returns Route', function () {
    $route = Router::get('/test', fn () => 'ok');
    expect($route)->toBeInstanceOf(Route::class);

    $result = $route->name('test');
    expect($result)->toBeInstanceOf(Route::class);
    expect($result->getName())->toBe('test');
});

test('route middleware method', function () {
    $route = Router::get('/protected', fn () => 'ok')->middleware('auth', 'csrf');
    expect($route->getMiddleware())->toContain('auth');
    expect($route->getMiddleware())->toContain('csrf');
});

// Reset
test('reset clears all routes', function () {
    Router::get('/a', fn () => 'A');
    Router::reset();

    expect(Router::dispatch('GET', '/a'))->toBeNull();
    expect(Router::getRoutes())->toBeEmpty();
});
