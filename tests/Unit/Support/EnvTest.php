<?php

declare(strict_types=1);

use Eymen\Support\Env;

beforeEach(function () {
    Env::reset();
});

test('load parses .env file', function () {
    $envDir = sys_get_temp_dir() . '/test_env_dir_' . uniqid();
    mkdir($envDir, 0755, true);
    file_put_contents($envDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\nAPP_PORT=8080\n");

    Env::load($envDir . '/.env');

    expect(Env::get('APP_NAME'))->toBe('TestApp');
    expect(Env::get('APP_DEBUG'))->toBe(true);   // castValue: 'true' → true
    expect(Env::get('APP_PORT'))->toBe(8080);     // castValue: '8080' → 8080

    unlink($envDir . '/.env');
    rmdir($envDir);
});

test('get returns default when key not found', function () {
    expect(Env::get('NONEXISTENT', 'default'))->toBe('default');
});

test('has checks if key exists', function () {
    $envPath = sys_get_temp_dir() . '/test_env_' . uniqid();
    file_put_contents($envPath, "MY_KEY=value\n");

    Env::load($envPath);

    expect(Env::has('MY_KEY'))->toBeTrue();
    expect(Env::has('MISSING_KEY'))->toBeFalse();

    unlink($envPath);
});

test('load handles comments and empty lines', function () {
    $envPath = sys_get_temp_dir() . '/test_env_' . uniqid();
    file_put_contents($envPath, "# This is a comment\n\nKEY=value\n# Another comment\nKEY2=value2\n");

    Env::load($envPath);

    expect(Env::get('KEY'))->toBe('value');
    expect(Env::get('KEY2'))->toBe('value2');

    unlink($envPath);
});

test('load handles quoted values', function () {
    $envPath = sys_get_temp_dir() . '/test_env_' . uniqid();
    file_put_contents($envPath, "KEY1=\"hello world\"\nKEY2='single quoted'\n");

    Env::load($envPath);

    expect(Env::get('KEY1'))->toBe('hello world');
    expect(Env::get('KEY2'))->toBe('single quoted');

    unlink($envPath);
});

test('reset clears all loaded values', function () {
    $envPath = sys_get_temp_dir() . '/test_env_' . uniqid();
    file_put_contents($envPath, "TEMP_KEY=temp_value\n");

    Env::load($envPath);
    expect(Env::get('TEMP_KEY'))->toBe('temp_value');

    Env::reset();
    expect(Env::isInitialized())->toBeFalse();

    unlink($envPath);
});

test('load handles variable interpolation', function () {
    $envPath = sys_get_temp_dir() . '/test_env_' . uniqid();
    file_put_contents($envPath, "BASE_URL=http://localhost\nAPI_URL=\${BASE_URL}/api\n");

    Env::load($envPath);

    expect(Env::get('BASE_URL'))->toBe('http://localhost');
    expect(Env::get('API_URL'))->toBe('http://localhost/api');

    unlink($envPath);
});
