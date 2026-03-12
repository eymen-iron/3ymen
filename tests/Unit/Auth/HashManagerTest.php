<?php

declare(strict_types=1);

use Eymen\Auth\HashManager;

test('make creates a hash', function () {
    $hasher = new HashManager();
    $hash = $hasher->make('password');

    expect($hash)->not->toBe('password');
    expect(strlen($hash))->toBeGreaterThan(20);
});

test('check verifies correct password', function () {
    $hasher = new HashManager();
    $hash = $hasher->make('secret123');

    expect($hasher->check('secret123', $hash))->toBeTrue();
});

test('check rejects wrong password', function () {
    $hasher = new HashManager();
    $hash = $hasher->make('correct');

    expect($hasher->check('wrong', $hash))->toBeFalse();
});

test('different passwords produce different hashes', function () {
    $hasher = new HashManager();
    $hash1 = $hasher->make('password1');
    $hash2 = $hasher->make('password2');

    expect($hash1)->not->toBe($hash2);
});

test('same password produces different hashes due to salt', function () {
    $hasher = new HashManager();
    $hash1 = $hasher->make('same');
    $hash2 = $hasher->make('same');

    expect($hash1)->not->toBe($hash2);
    expect($hasher->check('same', $hash1))->toBeTrue();
    expect($hasher->check('same', $hash2))->toBeTrue();
});

test('needsRehash detects outdated hash', function () {
    $hasher = new HashManager();
    $hash = $hasher->make('password');

    // Default bcrypt hash should not need rehash with same options
    expect($hasher->needsRehash($hash))->toBeFalse();
});

test('handles empty password', function () {
    $hasher = new HashManager();
    $hash = $hasher->make('');

    expect($hasher->check('', $hash))->toBeTrue();
    expect($hasher->check('notempty', $hash))->toBeFalse();
});

test('handles unicode password', function () {
    $hasher = new HashManager();
    $hash = $hasher->make('şifre123ğüı');

    expect($hasher->check('şifre123ğüı', $hash))->toBeTrue();
    expect($hasher->check('sifre123gui', $hash))->toBeFalse();
});

test('handles long password', function () {
    $hasher = new HashManager();
    $longPass = str_repeat('a', 200);
    $hash = $hasher->make($longPass);

    expect($hasher->check($longPass, $hash))->toBeTrue();
});
