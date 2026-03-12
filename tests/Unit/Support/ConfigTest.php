<?php

declare(strict_types=1);

use Eymen\Support\Config;

test('get retrieves config value with dot notation', function () {
    $config = new Config(items: [
        'app' => ['name' => 'TestApp', 'debug' => true],
        'database' => ['host' => 'localhost'],
    ]);

    expect($config->get('app.name'))->toBe('TestApp');
    expect($config->get('app.debug'))->toBeTrue();
    expect($config->get('database.host'))->toBe('localhost');
});

test('get returns default when key missing', function () {
    $config = new Config(items: ['app' => []]);
    expect($config->get('app.missing', 'fallback'))->toBe('fallback');
    expect($config->get('nonexistent.key', 'default'))->toBe('default');
});

test('set assigns value with dot notation', function () {
    $config = new Config();
    $config->set('app.name', 'MyApp');
    expect($config->get('app.name'))->toBe('MyApp');
});

test('has checks existence with dot notation', function () {
    $config = new Config(items: ['app' => ['name' => 'Test']]);
    expect($config->has('app.name'))->toBeTrue();
    expect($config->has('app.missing'))->toBeFalse();
});

test('all returns entire config', function () {
    $items = ['app' => ['name' => 'Test'], 'db' => ['host' => 'localhost']];
    $config = new Config(items: $items);
    expect($config->all())->toBe($items);
});

test('forget removes a key', function () {
    $config = new Config(items: ['app' => ['name' => 'Test', 'debug' => true]]);
    $config->forget('app.debug');
    expect($config->has('app.debug'))->toBeFalse();
    expect($config->has('app.name'))->toBeTrue();
});

test('push appends to array value', function () {
    $config = new Config(items: ['app' => ['providers' => ['A', 'B']]]);
    $config->push('app.providers', 'C');
    expect($config->get('app.providers'))->toBe(['A', 'B', 'C']);
});

test('merge merges items into config', function () {
    $config = new Config(items: ['app' => ['name' => 'Test']]);
    $config->merge(['app' => ['debug' => true]]);
    expect($config->get('app.debug'))->toBeTrue();
});

test('cache and loadFromCache work correctly', function () {
    $config = new Config(items: ['app' => ['name' => 'CachedApp']]);
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    $config->cache($cachePath);
    expect(file_exists($cachePath))->toBeTrue();

    $newConfig = new Config();
    $newConfig->loadFromCache($cachePath);
    expect($newConfig->get('app.name'))->toBe('CachedApp');

    unlink($cachePath);
});

test('load reads config files from directory', function () {
    $dir = sys_get_temp_dir() . '/config_test_' . uniqid();
    mkdir($dir, 0755, true);
    file_put_contents($dir . '/app.php', '<?php return ["name" => "DirApp", "debug" => false];');

    $config = new Config($dir);
    expect($config->get('app.name'))->toBe('DirApp');
    expect($config->get('app.debug'))->toBeFalse();

    unlink($dir . '/app.php');
    rmdir($dir);
});
