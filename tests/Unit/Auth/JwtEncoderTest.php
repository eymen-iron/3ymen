<?php

declare(strict_types=1);

use Eymen\Auth\JwtEncoder;

test('encode creates valid JWT token', function () {
    $encoder = new JwtEncoder();
    $payload = ['sub' => '1234', 'name' => 'Eymen', 'iat' => time()];
    $secret = 'test-secret-key-12345';

    $token = $encoder->encode($payload, $secret);

    expect($token)->toBeString();
    expect(substr_count($token, '.'))->toBe(2); // header.payload.signature
});

test('decode returns original payload', function () {
    $encoder = new JwtEncoder();
    $payload = ['sub' => '1234', 'name' => 'Eymen', 'iat' => time()];
    $secret = 'test-secret-key-12345';

    $token = $encoder->encode($payload, $secret);
    $decoded = $encoder->decode($token, $secret);

    expect($decoded['sub'])->toBe('1234');
    expect($decoded['name'])->toBe('Eymen');
});

test('decode fails with wrong secret', function () {
    $encoder = new JwtEncoder();
    $payload = ['sub' => '1', 'iat' => time()];
    $token = $encoder->encode($payload, 'correct-secret');

    $encoder->decode($token, 'wrong-secret');
})->throws(\RuntimeException::class);

test('decode fails with tampered token', function () {
    $encoder = new JwtEncoder();
    $payload = ['sub' => '1', 'iat' => time()];
    $secret = 'test-secret';
    $token = $encoder->encode($payload, $secret);

    // Tamper with payload
    $parts = explode('.', $token);
    $parts[1] = base64_encode(json_encode(['sub' => '999', 'iat' => time()]));
    $tampered = implode('.', $parts);

    $encoder->decode($tampered, $secret);
})->throws(\RuntimeException::class);

test('isExpired detects expired token', function () {
    $encoder = new JwtEncoder();
    $secret = 'test-secret';

    $expiredPayload = ['sub' => '1', 'exp' => time() - 3600]; // expired 1 hour ago
    $expiredToken = $encoder->encode($expiredPayload, $secret);
    expect($encoder->isExpired($expiredToken))->toBeTrue();

    $validPayload = ['sub' => '1', 'exp' => time() + 3600]; // expires in 1 hour
    $validToken = $encoder->encode($validPayload, $secret);
    expect($encoder->isExpired($validToken))->toBeFalse();
});

test('token without exp is not expired', function () {
    $encoder = new JwtEncoder();
    $token = $encoder->encode(['sub' => '1'], 'secret');

    expect($encoder->isExpired($token))->toBeFalse();
});

test('encode with HS256 algorithm', function () {
    $encoder = new JwtEncoder();
    $payload = ['data' => 'test'];
    $secret = 'hs256-secret';

    $token = $encoder->encode($payload, $secret, 'HS256');
    $decoded = $encoder->decode($token, $secret, 'HS256');

    expect($decoded['data'])->toBe('test');
});

test('roundtrip with complex payload', function () {
    $encoder = new JwtEncoder();
    $payload = [
        'sub' => '42',
        'name' => 'Eymen Demir',
        'roles' => ['admin', 'user'],
        'meta' => ['key' => 'value'],
        'iat' => time(),
        'exp' => time() + 3600,
    ];
    $secret = 'complex-secret-key';

    $token = $encoder->encode($payload, $secret);
    $decoded = $encoder->decode($token, $secret);

    expect($decoded['sub'])->toBe('42');
    expect($decoded['name'])->toBe('Eymen Demir');
    expect($decoded['roles'])->toBe(['admin', 'user']);
    expect($decoded['meta'])->toBe(['key' => 'value']);
});
