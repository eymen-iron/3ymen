<?php

declare(strict_types=1);

use Eymen\Http\Request;
use Eymen\Http\Uri;
use Eymen\Http\Stream\StringStream;

test('constructor sets method and URI', function () {
    $uri = Uri::fromString('http://localhost/test');
    $request = new Request(method: 'POST', uri: $uri);

    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->getPath())->toBe('/test');
});

test('getMethod returns HTTP method', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    expect($request->getMethod())->toBe('GET');
});

test('withMethod returns new instance with different method', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withMethod('POST');

    expect($new->getMethod())->toBe('POST');
    expect($request->getMethod())->toBe('GET');
});

test('withUri returns new instance with different URI', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/old'));
    $new = $request->withUri(Uri::fromString('/new'));

    expect($new->getUri()->getPath())->toBe('/new');
    expect($request->getUri()->getPath())->toBe('/old');
});

// Headers
test('withHeader sets and retrieves headers', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withHeader('Content-Type', 'application/json');

    expect($new->hasHeader('Content-Type'))->toBeTrue();
    expect($new->getHeaderLine('Content-Type'))->toBe('application/json');
    expect($request->hasHeader('Content-Type'))->toBeFalse();
});

test('withAddedHeader appends header value', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request
        ->withHeader('Accept', 'text/html')
        ->withAddedHeader('Accept', 'application/json');

    expect($new->getHeader('Accept'))->toBe(['text/html', 'application/json']);
    expect($new->getHeaderLine('Accept'))->toBe('text/html, application/json');
});

test('withoutHeader removes header', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'), headers: ['X-Custom' => ['value']]);
    $new = $request->withoutHeader('X-Custom');

    expect($new->hasHeader('X-Custom'))->toBeFalse();
    expect($request->hasHeader('X-Custom'))->toBeTrue();
});

test('getHeaders returns all headers', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'), headers: [
        'Accept' => ['text/html'],
        'Host' => ['localhost'],
    ]);

    $headers = $request->getHeaders();
    expect($headers)->toHaveKey('Accept');
    expect($headers)->toHaveKey('Host');
});

// Body
test('withBody replaces body stream', function () {
    $request = new Request(method: 'POST', uri: Uri::fromString('/'));
    $new = $request->withBody(new StringStream('hello'));

    expect((string) $new->getBody())->toBe('hello');
});

// Query params
test('withQueryParams returns new instance', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withQueryParams(['page' => '2', 'sort' => 'name']);

    expect($new->getQueryParams())->toBe(['page' => '2', 'sort' => 'name']);
    expect($request->getQueryParams())->toBe([]);
});

// Parsed body
test('withParsedBody returns new instance', function () {
    $request = new Request(method: 'POST', uri: Uri::fromString('/'));
    $new = $request->withParsedBody(['name' => 'Eymen', 'email' => 'test@test.com']);

    expect($new->getParsedBody())->toBe(['name' => 'Eymen', 'email' => 'test@test.com']);
    expect($request->getParsedBody())->toBeNull();
});

// Cookies
test('withCookieParams returns new instance', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withCookieParams(['session' => 'abc123']);

    expect($new->getCookieParams())->toBe(['session' => 'abc123']);
    expect($request->getCookieParams())->toBe([]);
});

// Attributes
test('withAttribute and getAttribute work correctly', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withAttribute('user_id', 42);

    expect($new->getAttribute('user_id'))->toBe(42);
    expect($new->getAttribute('missing', 'default'))->toBe('default');
    expect($request->getAttribute('user_id'))->toBeNull();
});

test('withoutAttribute removes attribute', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withAttribute('key', 'value')->withoutAttribute('key');

    expect($new->getAttribute('key'))->toBeNull();
});

test('getAttributes returns all attributes', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withAttribute('a', 1)->withAttribute('b', 2);

    expect($new->getAttributes())->toBe(['a' => 1, 'b' => 2]);
});

// Protocol version
test('withProtocolVersion returns new instance', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'));
    $new = $request->withProtocolVersion('2.0');

    expect($new->getProtocolVersion())->toBe('2.0');
    expect($request->getProtocolVersion())->toBe('1.1');
});

// Server params
test('getServerParams returns server parameters', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('/'), serverParams: ['SERVER_NAME' => 'localhost']);

    expect($request->getServerParams())->toBe(['SERVER_NAME' => 'localhost']);
});

// Request target
test('getRequestTarget returns path and query', function () {
    $request = new Request(method: 'GET', uri: Uri::fromString('http://localhost/path?q=1'));
    $target = $request->getRequestTarget();

    expect($target)->toContain('/path');
});
