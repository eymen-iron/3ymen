<?php

declare(strict_types=1);

use Eymen\Http\Stream\StringStream;

test('StringStream returns content', function () {
    $stream = new StringStream('Hello World');
    expect((string) $stream)->toBe('Hello World');
});

test('StringStream getContents returns remaining content', function () {
    $stream = new StringStream('Hello World');
    expect($stream->getContents())->toBe('Hello World');
});

test('StringStream getSize returns length', function () {
    $stream = new StringStream('Hello');
    expect($stream->getSize())->toBe(5);
});

test('StringStream is readable', function () {
    $stream = new StringStream('test');
    expect($stream->isReadable())->toBeTrue();
});

test('StringStream is writable when not closed', function () {
    $stream = new StringStream('test');
    expect($stream->isWritable())->toBeTrue();
});

test('StringStream read returns bytes', function () {
    $stream = new StringStream('Hello World');
    expect($stream->read(5))->toBe('Hello');
    expect($stream->read(6))->toBe(' World');
});

test('StringStream tell returns position', function () {
    $stream = new StringStream('Hello');
    expect($stream->tell())->toBe(0);
    $stream->read(3);
    expect($stream->tell())->toBe(3);
});

test('StringStream rewind resets position', function () {
    $stream = new StringStream('Hello');
    $stream->read(5);
    $stream->rewind();
    expect($stream->tell())->toBe(0);
    expect($stream->read(5))->toBe('Hello');
});

test('StringStream seek moves position', function () {
    $stream = new StringStream('Hello World');
    $stream->seek(6);
    expect($stream->read(5))->toBe('World');
});

test('StringStream eof returns true at end', function () {
    $stream = new StringStream('Hi');
    expect($stream->eof())->toBeFalse();
    $stream->read(2);
    expect($stream->eof())->toBeTrue();
});

test('StringStream isSeekable returns true', function () {
    $stream = new StringStream('test');
    expect($stream->isSeekable())->toBeTrue();
});

test('empty StringStream works', function () {
    $stream = new StringStream('');
    expect((string) $stream)->toBe('');
    expect($stream->getSize())->toBe(0);
    expect($stream->eof())->toBeTrue();
});
