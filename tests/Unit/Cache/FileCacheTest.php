<?php

declare(strict_types=1);

use Eymen\Cache\Drivers\FileDriver;

beforeEach(function () {
    $this->cacheDir = sys_get_temp_dir() . '/eymen_cache_test_' . uniqid();
    mkdir($this->cacheDir, 0755, true);
    $this->cache = new FileDriver($this->cacheDir);
});

afterEach(function () {
    $this->cache->flush();
    @rmdir($this->cacheDir);
});

test('set and get cache value', function () {
    $this->cache->set('key1', 'value1');
    expect($this->cache->get('key1'))->toBe('value1');
});

test('get returns default for missing key', function () {
    expect($this->cache->get('missing', 'default'))->toBe('default');
});

test('has checks key existence', function () {
    $this->cache->set('exists', 'yes');

    expect($this->cache->has('exists'))->toBeTrue();
    expect($this->cache->has('missing'))->toBeFalse();
});

test('delete removes cached value', function () {
    $this->cache->set('delete_me', 'value');
    $this->cache->delete('delete_me');

    expect($this->cache->has('delete_me'))->toBeFalse();
    expect($this->cache->get('delete_me'))->toBeNull();
});

test('flush clears all cache', function () {
    $this->cache->set('a', 1);
    $this->cache->set('b', 2);
    $this->cache->set('c', 3);

    $this->cache->flush();

    expect($this->cache->has('a'))->toBeFalse();
    expect($this->cache->has('b'))->toBeFalse();
    expect($this->cache->has('c'))->toBeFalse();
});

test('cache stores complex data types', function () {
    $this->cache->set('array', ['a' => 1, 'b' => [2, 3]]);
    expect($this->cache->get('array'))->toBe(['a' => 1, 'b' => [2, 3]]);

    $this->cache->set('int', 42);
    expect($this->cache->get('int'))->toBe(42);

    $this->cache->set('float', 3.14);
    expect($this->cache->get('float'))->toBe(3.14);

    $this->cache->set('bool', true);
    expect($this->cache->get('bool'))->toBeTrue();

    $this->cache->set('null', null);
    expect($this->cache->get('null'))->toBeNull();
});

test('TTL expires cache entries', function () {
    $this->cache->set('short_lived', 'value', 1);
    expect($this->cache->get('short_lived'))->toBe('value');

    sleep(2);
    expect($this->cache->get('short_lived'))->toBeNull();
});

test('many retrieves multiple keys', function () {
    $this->cache->set('x', 1);
    $this->cache->set('y', 2);

    $result = $this->cache->many(['x', 'y', 'z']);
    expect($result)->toBe(['x' => 1, 'y' => 2, 'z' => null]);
});

test('setMany stores multiple values', function () {
    $this->cache->setMany(['a' => 10, 'b' => 20, 'c' => 30]);

    expect($this->cache->get('a'))->toBe(10);
    expect($this->cache->get('b'))->toBe(20);
    expect($this->cache->get('c'))->toBe(30);
});

test('increment and decrement', function () {
    $this->cache->set('counter', 10);

    expect($this->cache->increment('counter'))->toBe(11);
    expect($this->cache->increment('counter', 5))->toBe(16);
    expect($this->cache->decrement('counter', 3))->toBe(13);
    expect($this->cache->decrement('counter'))->toBe(12);
});

test('remember caches callback result', function () {
    $called = 0;
    $callback = function () use (&$called) {
        $called++;
        return 'computed';
    };

    $result1 = $this->cache->remember('computed_key', 60, $callback);
    $result2 = $this->cache->remember('computed_key', 60, $callback);

    expect($result1)->toBe('computed');
    expect($result2)->toBe('computed');
    expect($called)->toBe(1); // callback called only once
});

test('forever stores without expiry', function () {
    $this->cache->forever('permanent', 'stays');
    expect($this->cache->get('permanent'))->toBe('stays');
});

test('forget is alias for delete', function () {
    $this->cache->set('goodbye', 'value');
    $this->cache->forget('goodbye');

    expect($this->cache->has('goodbye'))->toBeFalse();
});
