<?php

declare(strict_types=1);

use Eymen\Support\Str;

// Case conversion
test('camel converts string to camelCase', function () {
    expect(Str::camel('foo_bar'))->toBe('fooBar');
    expect(Str::camel('foo-bar'))->toBe('fooBar');
    expect(Str::camel('Foo Bar'))->toBe('fooBar');
    expect(Str::camel('FooBar'))->toBe('fooBar');
});

test('snake converts string to snake_case', function () {
    expect(Str::snake('fooBar'))->toBe('foo_bar');
    expect(Str::snake('FooBar'))->toBe('foo_bar');
    expect(Str::snake('Foo Bar'))->toBe('foo_bar');
});

test('studly converts string to StudlyCase', function () {
    expect(Str::studly('foo_bar'))->toBe('FooBar');
    expect(Str::studly('foo-bar'))->toBe('FooBar');
    expect(Str::studly('foo bar'))->toBe('FooBar');
});

test('kebab converts string to kebab-case', function () {
    expect(Str::kebab('fooBar'))->toBe('foo-bar');
    expect(Str::kebab('FooBar'))->toBe('foo-bar');
});

test('slug creates URL-friendly slug', function () {
    expect(Str::slug('Hello World'))->toBe('hello-world');
    expect(Str::slug('Hello World', '_'))->toBe('hello_world');
    expect(Str::slug('  Hello   World  '))->toBe('hello-world');
});

// String checks
test('contains checks if string contains substring', function () {
    expect(Str::contains('Hello World', 'World'))->toBeTrue();
    expect(Str::contains('Hello World', 'world'))->toBeFalse();
    expect(Str::contains('Hello World', ['World', 'Foo']))->toBeTrue();
    expect(Str::contains('Hello World', ['Foo', 'Bar']))->toBeFalse();
});

test('startsWith checks string start', function () {
    expect(Str::startsWith('Hello World', 'Hello'))->toBeTrue();
    expect(Str::startsWith('Hello World', 'World'))->toBeFalse();
    expect(Str::startsWith('Hello World', ['Hello', 'Foo']))->toBeTrue();
});

test('endsWith checks string end', function () {
    expect(Str::endsWith('Hello World', 'World'))->toBeTrue();
    expect(Str::endsWith('Hello World', 'Hello'))->toBeFalse();
    expect(Str::endsWith('Hello World', ['World', 'Foo']))->toBeTrue();
});

// Extraction
test('after returns string after first occurrence', function () {
    expect(Str::after('Hello World', 'Hello '))->toBe('World');
    expect(Str::after('Hello World', 'missing'))->toBe('Hello World');
});

test('before returns string before first occurrence', function () {
    expect(Str::before('Hello World', ' World'))->toBe('Hello');
    expect(Str::before('Hello World', 'missing'))->toBe('Hello World');
});

test('between extracts string between two delimiters', function () {
    expect(Str::between('[hello]', '[', ']'))->toBe('hello');
    expect(Str::between('abc[hello]def', '[', ']'))->toBe('hello');
});

// Truncation
test('limit truncates string to given length', function () {
    $result = Str::limit('Hello World', 5);
    expect(mb_strlen($result))->toBeLessThanOrEqual(8); // 5 chars + '...'
    expect(Str::limit('Hello', 10))->toBe('Hello');
});

test('words limits string to given number of words', function () {
    expect(Str::words('one two three four', 2))->toBe('one two...');
    expect(Str::words('one two', 5))->toBe('one two');
});

// Case methods
test('upper converts to uppercase', function () {
    expect(Str::upper('hello'))->toBe('HELLO');
});

test('lower converts to lowercase', function () {
    expect(Str::lower('HELLO'))->toBe('hello');
});

test('title converts to title case', function () {
    expect(Str::title('hello world'))->toBe('Hello World');
});

test('ucfirst capitalizes first character', function () {
    expect(Str::ucfirst('hello'))->toBe('Hello');
});

test('lcfirst lowercases first character', function () {
    expect(Str::lcfirst('Hello'))->toBe('hello');
});

// Utility
test('length returns string length', function () {
    expect(Str::length('hello'))->toBe(5);
    expect(Str::length(''))->toBe(0);
});

test('substr extracts substring', function () {
    expect(Str::substr('Hello World', 6))->toBe('World');
    expect(Str::substr('Hello World', 0, 5))->toBe('Hello');
});

test('replace replaces occurrences', function () {
    expect(Str::replace('World', 'PHP', 'Hello World'))->toBe('Hello PHP');
});

test('random generates string of given length', function () {
    $random = Str::random(32);
    expect(strlen($random))->toBe(32);
    expect(Str::random(32))->not->toBe($random);
});

test('uuid generates valid UUID v4', function () {
    $uuid = Str::uuid();
    expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

// Trimming
test('trim removes whitespace', function () {
    expect(Str::trim('  hello  '))->toBe('hello');
    expect(Str::trim('xxhelloxx', 'x'))->toBe('hello');
});

test('ltrim removes leading whitespace', function () {
    expect(Str::ltrim('  hello  '))->toBe('hello  ');
});

test('rtrim removes trailing whitespace', function () {
    expect(Str::rtrim('  hello  '))->toBe('  hello');
});

// Empty checks
test('isEmpty checks for empty string', function () {
    expect(Str::isEmpty(''))->toBeTrue();
    expect(Str::isEmpty(null))->toBeTrue();
    expect(Str::isEmpty('hello'))->toBeFalse();
});

test('isNotEmpty checks for non-empty string', function () {
    expect(Str::isNotEmpty('hello'))->toBeTrue();
    expect(Str::isNotEmpty(''))->toBeFalse();
    expect(Str::isNotEmpty(null))->toBeFalse();
});

// Padding
test('finish ensures string ends with cap', function () {
    expect(Str::finish('hello', '/'))->toBe('hello/');
    expect(Str::finish('hello/', '/'))->toBe('hello/');
});

test('start ensures string starts with prefix', function () {
    expect(Str::start('hello', '/'))->toBe('/hello');
    expect(Str::start('/hello', '/'))->toBe('/hello');
});

test('padLeft pads string on the left', function () {
    expect(Str::padLeft('hello', 10))->toBe('     hello');
    expect(Str::padLeft('hello', 10, '*'))->toBe('*****hello');
});

test('padRight pads string on the right', function () {
    expect(Str::padRight('hello', 10))->toBe('hello     ');
    expect(Str::padRight('hello', 10, '*'))->toBe('hello*****');
});

// Wrapping
test('wrap wraps string with before and after', function () {
    expect(Str::wrap('hello', '"'))->toBe('"hello"');
    expect(Str::wrap('hello', '[', ']'))->toBe('[hello]');
});

test('unwrap removes wrapper from string', function () {
    expect(Str::unwrap('"hello"', '"'))->toBe('hello');
    expect(Str::unwrap('[hello]', '[', ']'))->toBe('hello');
});

// Masking
test('mask replaces characters with mask character', function () {
    expect(Str::mask('secret@email.com', '*', 3))->toBe('sec*************');
    expect(Str::mask('1234567890', '*', 0, 6))->toBe('******7890');
});

// Pattern matching
test('is checks if string matches pattern', function () {
    expect(Str::is('foo*', 'foobar'))->toBeTrue();
    expect(Str::is('foo*', 'bar'))->toBeFalse();
    expect(Str::is(['foo*', 'bar*'], 'foobar'))->toBeTrue();
});

// Pluralization
test('plural returns plural form', function () {
    expect(Str::plural('car'))->toBe('cars');
    expect(Str::plural('child'))->toBe('children');
});

test('singular returns singular form', function () {
    expect(Str::singular('cars'))->toBe('car');
    expect(Str::singular('children'))->toBe('child');
});
