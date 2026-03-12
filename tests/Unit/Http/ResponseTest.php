<?php

declare(strict_types=1);

use Eymen\Http\Response;
use Eymen\Http\Stream\StringStream;

test('default response is 200 OK', function () {
    $response = new Response();
    expect($response->getStatusCode())->toBe(200);
    expect($response->getReasonPhrase())->toBe('OK');
});

test('withStatus returns new instance', function () {
    $response = new Response();
    $new = $response->withStatus(404, 'Not Found');

    expect($new->getStatusCode())->toBe(404);
    expect($new->getReasonPhrase())->toBe('Not Found');
    expect($response->getStatusCode())->toBe(200);
});

test('withStatus uses default reason phrase', function () {
    $response = (new Response())->withStatus(201);
    expect($response->getStatusCode())->toBe(201);
    expect($response->getReasonPhrase())->not->toBeEmpty();
});

test('common status codes', function () {
    expect((new Response(statusCode: 200))->getReasonPhrase())->toBe('OK');
    expect((new Response(statusCode: 301))->getReasonPhrase())->not->toBeEmpty();
    expect((new Response(statusCode: 404))->getReasonPhrase())->not->toBeEmpty();
    expect((new Response(statusCode: 500))->getReasonPhrase())->not->toBeEmpty();
});

// Headers
test('withHeader sets header immutably', function () {
    $response = new Response();
    $new = $response->withHeader('Content-Type', 'application/json');

    expect($new->hasHeader('Content-Type'))->toBeTrue();
    expect($new->getHeaderLine('Content-Type'))->toBe('application/json');
    expect($response->hasHeader('Content-Type'))->toBeFalse();
});

test('withAddedHeader appends header value', function () {
    $response = (new Response())
        ->withHeader('X-Custom', 'a')
        ->withAddedHeader('X-Custom', 'b');

    expect($response->getHeader('X-Custom'))->toBe(['a', 'b']);
    expect($response->getHeaderLine('X-Custom'))->toBe('a, b');
});

test('withoutHeader removes header', function () {
    $response = (new Response())->withHeader('X-Remove', 'val');
    $new = $response->withoutHeader('X-Remove');

    expect($new->hasHeader('X-Remove'))->toBeFalse();
});

test('getHeaders returns all headers', function () {
    $response = (new Response())
        ->withHeader('Content-Type', 'text/html')
        ->withHeader('X-Custom', 'value');

    expect($response->getHeaders())->toHaveKey('Content-Type');
    expect($response->getHeaders())->toHaveKey('X-Custom');
});

// Body
test('withBody sets body stream', function () {
    $response = new Response();
    $new = $response->withBody(new StringStream('Hello World'));

    expect((string) $new->getBody())->toBe('Hello World');
});

test('body is readable as string', function () {
    $body = new StringStream('{"status":"ok"}');
    $response = new Response(body: $body);

    expect((string) $response->getBody())->toBe('{"status":"ok"}');
});

// Protocol
test('withProtocolVersion returns new instance', function () {
    $response = new Response();
    $new = $response->withProtocolVersion('2.0');

    expect($new->getProtocolVersion())->toBe('2.0');
    expect($response->getProtocolVersion())->toBe('1.1');
});

// Constructor
test('constructor accepts all parameters', function () {
    $body = new StringStream('test body');
    $response = new Response(
        statusCode: 201,
        headers: ['X-Test' => ['yes']],
        body: $body,
        reasonPhrase: 'Created',
        protocolVersion: '1.0'
    );

    expect($response->getStatusCode())->toBe(201);
    expect($response->getReasonPhrase())->toBe('Created');
    expect($response->getProtocolVersion())->toBe('1.0');
    expect($response->hasHeader('X-Test'))->toBeTrue();
    expect((string) $response->getBody())->toBe('test body');
});
