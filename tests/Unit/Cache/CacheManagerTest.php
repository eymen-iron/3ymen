<?php

declare(strict_types=1);

use Eymen\Cache\CacheManager;

test('cache manager creates file driver by default', function () {
    $cacheDir = sys_get_temp_dir() . '/cache_mgr_test_' . uniqid();
    mkdir($cacheDir, 0755, true);

    $manager = new CacheManager([
        'driver' => 'file',
        'path' => $cacheDir,
        'prefix' => 'test_',
    ]);

    expect($manager->getDriverName())->toBe('file');
    expect($manager->getDriver())->toBeInstanceOf(\Eymen\Cache\CacheInterface::class);

    $manager->flush();
    @rmdir($cacheDir);
});

test('cache manager proxies get/set to driver', function () {
    $cacheDir = sys_get_temp_dir() . '/cache_mgr_test_' . uniqid();
    mkdir($cacheDir, 0755, true);

    $manager = new CacheManager([
        'driver' => 'file',
        'path' => $cacheDir,
        'prefix' => 'test_',
    ]);

    $manager->set('key', 'value');
    expect($manager->get('key'))->toBe('value');
    expect($manager->has('key'))->toBeTrue();

    $manager->delete('key');
    expect($manager->has('key'))->toBeFalse();

    $manager->flush();
    @rmdir($cacheDir);
});

test('cache manager remember works', function () {
    $cacheDir = sys_get_temp_dir() . '/cache_mgr_test_' . uniqid();
    mkdir($cacheDir, 0755, true);

    $manager = new CacheManager([
        'driver' => 'file',
        'path' => $cacheDir,
        'prefix' => 'test_',
    ]);

    $count = 0;
    $result = $manager->remember('lazy', 60, function () use (&$count) {
        $count++;
        return 'computed';
    });

    expect($result)->toBe('computed');
    expect($count)->toBe(1);

    $manager->remember('lazy', 60, function () use (&$count) {
        $count++;
        return 'again';
    });

    expect($count)->toBe(1);

    $manager->flush();
    @rmdir($cacheDir);
});
