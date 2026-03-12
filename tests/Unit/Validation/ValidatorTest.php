<?php

declare(strict_types=1);

use Eymen\Validation\Validator;

// Basic validation
test('passes returns true for valid data', function () {
    $v = Validator::make(
        ['name' => 'Eymen', 'email' => 'test@test.com'],
        ['name' => 'required|string', 'email' => 'required|email']
    );

    expect($v->passes())->toBeTrue();
    expect($v->fails())->toBeFalse();
    expect($v->errors())->toBeEmpty();
});

test('fails returns true for invalid data', function () {
    $v = Validator::make(
        ['name' => '', 'email' => 'not-an-email'],
        ['name' => 'required', 'email' => 'email']
    );

    expect($v->fails())->toBeTrue();
    expect($v->passes())->toBeFalse();
    expect($v->errors())->not->toBeEmpty();
});

test('validated returns only validated fields', function () {
    $v = Validator::make(
        ['name' => 'Eymen', 'age' => 25, 'extra' => 'not in rules'],
        ['name' => 'required|string', 'age' => 'required|integer']
    );

    $validated = $v->validated();
    expect($validated)->toHaveKey('name');
    expect($validated)->toHaveKey('age');
    expect($validated)->not->toHaveKey('extra');
});

// Required rule
test('required fails on empty string', function () {
    $v = Validator::make(['field' => ''], ['field' => 'required']);
    expect($v->fails())->toBeTrue();
});

test('required fails on null', function () {
    $v = Validator::make([], ['field' => 'required']);
    expect($v->fails())->toBeTrue();
});

test('required passes on non-empty value', function () {
    $v = Validator::make(['field' => 'value'], ['field' => 'required']);
    expect($v->passes())->toBeTrue();
});

// String rule
test('string passes for string values', function () {
    $v = Validator::make(['name' => 'hello'], ['name' => 'string']);
    expect($v->passes())->toBeTrue();
});

test('string fails for non-string values', function () {
    $v = Validator::make(['name' => 123], ['name' => 'string']);
    expect($v->fails())->toBeTrue();
});

// Email rule
test('email validates proper email', function () {
    $v = Validator::make(['email' => 'user@example.com'], ['email' => 'email']);
    expect($v->passes())->toBeTrue();
});

test('email fails for invalid email', function () {
    $v = Validator::make(['email' => 'not-email'], ['email' => 'email']);
    expect($v->fails())->toBeTrue();
});

// Numeric rules
test('numeric validates numbers', function () {
    $v = Validator::make(['price' => '19.99'], ['price' => 'numeric']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['price' => 'abc'], ['price' => 'numeric']);
    expect($v->fails())->toBeTrue();
});

test('integer validates integers', function () {
    $v = Validator::make(['count' => 42], ['count' => 'integer']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['count' => 3.14], ['count' => 'integer']);
    expect($v->fails())->toBeTrue();
});

// Min/Max rules
test('min validates minimum length for strings', function () {
    $v = Validator::make(['name' => 'ab'], ['name' => 'string|min:3']);
    expect($v->fails())->toBeTrue();

    $v = Validator::make(['name' => 'abc'], ['name' => 'string|min:3']);
    expect($v->passes())->toBeTrue();
});

test('max validates maximum length for strings', function () {
    $v = Validator::make(['name' => 'abcdef'], ['name' => 'string|max:5']);
    expect($v->fails())->toBeTrue();

    $v = Validator::make(['name' => 'abc'], ['name' => 'string|max:5']);
    expect($v->passes())->toBeTrue();
});

test('min validates minimum value for numbers', function () {
    $v = Validator::make(['age' => 15], ['age' => 'integer|min:18']);
    expect($v->fails())->toBeTrue();

    $v = Validator::make(['age' => 25], ['age' => 'integer|min:18']);
    expect($v->passes())->toBeTrue();
});

test('max validates maximum value for numbers', function () {
    $v = Validator::make(['age' => 200], ['age' => 'integer|max:150']);
    expect($v->fails())->toBeTrue();
});

// Between rule
test('between validates range', function () {
    $v = Validator::make(['age' => 25], ['age' => 'integer|between:18,65']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['age' => 10], ['age' => 'integer|between:18,65']);
    expect($v->fails())->toBeTrue();
});

// In rule
test('in validates value is in list', function () {
    $v = Validator::make(['status' => 'active'], ['status' => 'in:active,inactive,pending']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['status' => 'deleted'], ['status' => 'in:active,inactive,pending']);
    expect($v->fails())->toBeTrue();
});

// NotIn rule
test('not_in validates value is not in list', function () {
    $v = Validator::make(['role' => 'admin'], ['role' => 'not_in:banned,suspended']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['role' => 'banned'], ['role' => 'not_in:banned,suspended']);
    expect($v->fails())->toBeTrue();
});

// Confirmed rule
test('confirmed validates password confirmation', function () {
    $v = Validator::make(
        ['password' => 'secret', 'password_confirmation' => 'secret'],
        ['password' => 'confirmed']
    );
    expect($v->passes())->toBeTrue();

    $v = Validator::make(
        ['password' => 'secret', 'password_confirmation' => 'different'],
        ['password' => 'confirmed']
    );
    expect($v->fails())->toBeTrue();
});

// URL rule
test('url validates URLs', function () {
    $v = Validator::make(['site' => 'https://example.com'], ['site' => 'url']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['site' => 'not-a-url'], ['site' => 'url']);
    expect($v->fails())->toBeTrue();
});

// IP rule
test('ip validates IP addresses', function () {
    $v = Validator::make(['ip' => '192.168.1.1'], ['ip' => 'ip']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['ip' => '999.999.999.999'], ['ip' => 'ip']);
    expect($v->fails())->toBeTrue();
});

// Date rules
test('date validates date format', function () {
    $v = Validator::make(['dob' => '2000-01-15'], ['dob' => 'date']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['dob' => 'not-a-date'], ['dob' => 'date']);
    expect($v->fails())->toBeTrue();
});

// Alpha rules
test('alpha validates alphabetic characters', function () {
    $v = Validator::make(['name' => 'Eymen'], ['name' => 'alpha']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['name' => 'Eymen123'], ['name' => 'alpha']);
    expect($v->fails())->toBeTrue();
});

test('alpha_num validates alphanumeric', function () {
    $v = Validator::make(['code' => 'abc123'], ['code' => 'alpha_num']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['code' => 'abc-123'], ['code' => 'alpha_num']);
    expect($v->fails())->toBeTrue();
});

// Regex rule
test('regex validates pattern', function () {
    $v = Validator::make(['code' => 'ABC-123'], ['code' => 'regex:/^[A-Z]{3}-[0-9]{3}$/']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['code' => 'abc-123'], ['code' => 'regex:/^[A-Z]{3}-[0-9]{3}$/']);
    expect($v->fails())->toBeTrue();
});

// Nullable rule
test('nullable allows null values', function () {
    $v = Validator::make(['bio' => null], ['bio' => 'nullable|string']);
    expect($v->passes())->toBeTrue();
});

// Boolean rule
test('boolean validates boolean values', function () {
    $v = Validator::make(['active' => true], ['active' => 'boolean']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['active' => false], ['active' => 'boolean']);
    expect($v->passes())->toBeTrue();
});

// JSON rule
test('json validates JSON strings', function () {
    $v = Validator::make(['data' => '{"key":"value"}'], ['data' => 'json']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['data' => '{invalid}'], ['data' => 'json']);
    expect($v->fails())->toBeTrue();
});

// Custom messages
test('custom error messages are used', function () {
    $v = Validator::make(
        ['name' => ''],
        ['name' => 'required'],
        ['name.required' => 'Ad alanı zorunludur.']
    );

    $v->fails();
    $errors = $v->errors();
    expect($errors['name'][0] ?? '')->toBe('Ad alanı zorunludur.');
});

// Multiple rules
test('multiple rules are all validated', function () {
    $v = Validator::make(
        ['password' => 'ab'],
        ['password' => 'required|string|min:8|max:100']
    );

    expect($v->fails())->toBeTrue();
    $errors = $v->errors();
    expect($errors)->toHaveKey('password');
});

// Custom rule extension
test('extend adds custom validation rule', function () {
    Validator::extend('even', function ($attribute, $value, $parameters, $data) {
        return is_numeric($value) && $value % 2 === 0;
    }, 'The :attribute must be even.');

    $v = Validator::make(['num' => 4], ['num' => 'even']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['num' => 3], ['num' => 'even']);
    expect($v->fails())->toBeTrue();
});

// Array rule
test('array validates array values', function () {
    $v = Validator::make(['tags' => ['php', 'js']], ['tags' => 'array']);
    expect($v->passes())->toBeTrue();

    $v = Validator::make(['tags' => 'not-array'], ['tags' => 'array']);
    expect($v->fails())->toBeTrue();
});
