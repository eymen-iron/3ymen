<?php

declare(strict_types=1);

use Eymen\Support\Arr;

// Dot notation access
test('get retrieves value using dot notation', function () {
    $array = ['user' => ['name' => 'Eymen', 'age' => 25]];
    expect(Arr::get($array, 'user.name'))->toBe('Eymen');
    expect(Arr::get($array, 'user.age'))->toBe(25);
    expect(Arr::get($array, 'user.email', 'default'))->toBe('default');
    expect(Arr::get($array, 'missing', 'fallback'))->toBe('fallback');
});

test('set assigns value using dot notation', function () {
    $array = [];
    Arr::set($array, 'user.name', 'Eymen');
    expect($array)->toBe(['user' => ['name' => 'Eymen']]);

    Arr::set($array, 'user.age', 25);
    expect($array['user']['age'])->toBe(25);
});

test('has checks existence using dot notation', function () {
    $array = ['user' => ['name' => 'Eymen']];
    expect(Arr::has($array, 'user.name'))->toBeTrue();
    expect(Arr::has($array, 'user.email'))->toBeFalse();
    expect(Arr::has($array, 'missing'))->toBeFalse();
});

test('forget removes value using dot notation', function () {
    $array = ['user' => ['name' => 'Eymen', 'age' => 25]];
    Arr::forget($array, 'user.age');
    expect($array)->toBe(['user' => ['name' => 'Eymen']]);
});

// Flattening
test('dot flattens multi-dimensional array', function () {
    $array = ['user' => ['name' => 'Eymen', 'address' => ['city' => 'Istanbul']]];
    expect(Arr::dot($array))->toBe([
        'user.name' => 'Eymen',
        'user.address.city' => 'Istanbul',
    ]);
});

// Filtering
test('only returns specified keys', function () {
    $array = ['name' => 'Eymen', 'age' => 25, 'email' => 'test@test.com'];
    expect(Arr::only($array, ['name', 'email']))->toBe([
        'name' => 'Eymen',
        'email' => 'test@test.com',
    ]);
});

test('except returns all except specified keys', function () {
    $array = ['name' => 'Eymen', 'age' => 25, 'email' => 'test@test.com'];
    expect(Arr::except($array, ['age']))->toBe([
        'name' => 'Eymen',
        'email' => 'test@test.com',
    ]);
});

// First and last
test('first returns first element or matching element', function () {
    expect(Arr::first([1, 2, 3]))->toBe(1);
    expect(Arr::first([1, 2, 3], fn ($v) => $v > 1))->toBe(2);
    expect(Arr::first([], null, 'default'))->toBe('default');
});

test('last returns last element or matching element', function () {
    expect(Arr::last([1, 2, 3]))->toBe(3);
    expect(Arr::last([1, 2, 3], fn ($v) => $v < 3))->toBe(2);
    expect(Arr::last([], null, 'default'))->toBe('default');
});

// Higher-order
test('every checks if all elements pass callback', function () {
    expect(Arr::every([2, 4, 6], fn ($v) => $v % 2 === 0))->toBeTrue();
    expect(Arr::every([2, 3, 6], fn ($v) => $v % 2 === 0))->toBeFalse();
});

test('where filters array by callback', function () {
    $result = Arr::where([1, 2, 3, 4, 5], fn ($v) => $v > 3);
    expect(array_values($result))->toBe([4, 5]);
});

// Sorting
test('sortRecursive sorts arrays recursively', function () {
    $array = ['b' => ['z' => 1, 'a' => 2], 'a' => 3];
    $sorted = Arr::sortRecursive($array);
    expect(array_keys($sorted))->toBe(['a', 'b']);
    expect(array_keys($sorted['b']))->toBe(['a', 'z']);
});
