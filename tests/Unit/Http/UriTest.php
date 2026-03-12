<?php

declare(strict_types=1);

use Eymen\Http\Uri;

test('fromString parses full URI', function () {
    $uri = Uri::fromString('https://user:pass@example.com:8080/path/to/page?key=val&foo=bar#section');

    expect($uri->getScheme())->toBe('https');
    expect($uri->getUserInfo())->toBe('user:pass');
    expect($uri->getHost())->toBe('example.com');
    expect($uri->getPort())->toBe(8080);
    expect($uri->getPath())->toBe('/path/to/page');
    expect($uri->getQuery())->toBe('key=val&foo=bar');
    expect($uri->getFragment())->toBe('section');
});

test('fromString parses simple URI', function () {
    $uri = Uri::fromString('http://localhost/');
    expect($uri->getScheme())->toBe('http');
    expect($uri->getHost())->toBe('localhost');
    expect($uri->getPath())->toBe('/');
    expect($uri->getPort())->toBeNull();
});

test('getAuthority returns user@host:port', function () {
    $uri = Uri::fromString('https://user:pass@example.com:8080/path');
    expect($uri->getAuthority())->toBe('user:pass@example.com:8080');

    $uri2 = Uri::fromString('https://example.com/path');
    expect($uri2->getAuthority())->toBe('example.com');
});

test('withScheme returns new instance', function () {
    $uri = Uri::fromString('http://example.com');
    $new = $uri->withScheme('https');

    expect($new->getScheme())->toBe('https');
    expect($uri->getScheme())->toBe('http'); // immutable
});

test('withHost returns new instance', function () {
    $uri = Uri::fromString('http://example.com');
    $new = $uri->withHost('other.com');

    expect($new->getHost())->toBe('other.com');
    expect($uri->getHost())->toBe('example.com');
});

test('withPort returns new instance', function () {
    $uri = Uri::fromString('http://example.com');
    $new = $uri->withPort(9090);

    expect($new->getPort())->toBe(9090);
    expect($uri->getPort())->toBeNull();
});

test('withPath returns new instance', function () {
    $uri = Uri::fromString('http://example.com/old');
    $new = $uri->withPath('/new');

    expect($new->getPath())->toBe('/new');
    expect($uri->getPath())->toBe('/old');
});

test('withQuery returns new instance', function () {
    $uri = Uri::fromString('http://example.com?old=1');
    $new = $uri->withQuery('new=2');

    expect($new->getQuery())->toBe('new=2');
    expect($uri->getQuery())->toBe('old=1');
});

test('withFragment returns new instance', function () {
    $uri = Uri::fromString('http://example.com#old');
    $new = $uri->withFragment('new');

    expect($new->getFragment())->toBe('new');
    expect($uri->getFragment())->toBe('old');
});

test('withUserInfo returns new instance', function () {
    $uri = Uri::fromString('http://example.com');
    $new = $uri->withUserInfo('admin', 'secret');

    expect($new->getUserInfo())->toBe('admin:secret');
    expect($uri->getUserInfo())->toBe('');
});

test('toString reconstructs full URI', function () {
    $uri = Uri::fromString('https://example.com:8080/path?q=1#frag');
    expect((string) $uri)->toBe('https://example.com:8080/path?q=1#frag');
});

test('toString with default port omits port', function () {
    $uri = new Uri(scheme: 'https', host: 'example.com', port: 443, path: '/');
    $str = (string) $uri;
    expect($str)->not->toContain(':443');
});

test('empty URI parts produce empty string', function () {
    $uri = new Uri();
    expect($uri->getScheme())->toBe('');
    expect($uri->getHost())->toBe('');
    expect($uri->getPath())->toBe('');
    expect($uri->getQuery())->toBe('');
    expect($uri->getFragment())->toBe('');
    expect($uri->getUserInfo())->toBe('');
    expect($uri->getPort())->toBeNull();
});
