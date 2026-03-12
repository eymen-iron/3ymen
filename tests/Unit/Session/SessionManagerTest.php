<?php

declare(strict_types=1);

use Eymen\Session\SessionManager;

beforeEach(function () {
    $this->sessionDir = sys_get_temp_dir() . '/eymen_session_test_' . uniqid();
    mkdir($this->sessionDir, 0755, true);

    $this->session = new SessionManager([
        'driver' => 'file',
        'path' => $this->sessionDir,
        'lifetime' => 120,
        'cookie' => 'eymen_session',
    ]);
});

afterEach(function () {
    if ($this->session->isStarted()) {
        $this->session->destroy();
    }
    array_map('unlink', glob($this->sessionDir . '/*') ?: []);
    @rmdir($this->sessionDir);
});

test('session starts and has ID', function () {
    $this->session->start();

    expect($this->session->isStarted())->toBeTrue();
    expect($this->session->getId())->not->toBeEmpty();
});

test('set and get values', function () {
    $this->session->start();
    $this->session->set('name', 'Eymen');
    $this->session->set('age', 25);

    expect($this->session->get('name'))->toBe('Eymen');
    expect($this->session->get('age'))->toBe(25);
});

test('get returns default for missing key', function () {
    $this->session->start();
    expect($this->session->get('missing', 'default'))->toBe('default');
});

test('has checks key existence', function () {
    $this->session->start();
    $this->session->set('key', 'value');

    expect($this->session->has('key'))->toBeTrue();
    expect($this->session->has('missing'))->toBeFalse();
});

test('remove deletes a key', function () {
    $this->session->start();
    $this->session->set('remove_me', 'value');
    $this->session->remove('remove_me');

    expect($this->session->has('remove_me'))->toBeFalse();
});

test('all returns all session data', function () {
    $this->session->start();
    $this->session->set('a', 1);
    $this->session->set('b', 2);

    $all = $this->session->all();
    expect($all)->toHaveKey('a');
    expect($all)->toHaveKey('b');
});

test('clear removes all data', function () {
    $this->session->start();
    $this->session->set('a', 1);
    $this->session->set('b', 2);
    $this->session->clear();

    expect($this->session->all())->toBeEmpty();
});

test('flash stores temporary data', function () {
    $this->session->start();
    $this->session->flash('message', 'Success!');

    expect($this->session->getFlash('message'))->toBe('Success!');
    expect($this->session->hasFlash('message'))->toBeTrue();
});

test('flash data has default', function () {
    $this->session->start();
    expect($this->session->getFlash('missing', 'fallback'))->toBe('fallback');
});

test('token generates CSRF token', function () {
    $this->session->start();
    $token = $this->session->token();

    expect($token)->not->toBeEmpty();
    expect(strlen($token))->toBeGreaterThanOrEqual(20);

    // Same token on subsequent calls
    expect($this->session->token())->toBe($token);
});

test('regenerate creates new session ID', function () {
    $this->session->start();
    $oldId = $this->session->getId();

    $this->session->regenerate();
    $newId = $this->session->getId();

    expect($newId)->not->toBe($oldId);
});

test('setId and getId work correctly', function () {
    $this->session->start();
    $this->session->setId('custom-session-id');

    expect($this->session->getId())->toBe('custom-session-id');
});

test('getName returns session name', function () {
    $name = $this->session->getName();
    expect($name)->toBeString();
    expect($name)->not->toBeEmpty();
});

test('getDriverName returns driver type', function () {
    expect($this->session->getDriverName())->toBe('file');
});

test('previousUrl and setPreviousUrl', function () {
    $this->session->start();
    $this->session->setPreviousUrl('/dashboard');

    expect($this->session->previousUrl())->toBe('/dashboard');
});

test('save persists session data', function () {
    $this->session->start();
    $this->session->set('persist', 'me');
    $this->session->save();

    // Session should still have the data
    expect($this->session->get('persist'))->toBe('me');
});

test('invalidate destroys and regenerates', function () {
    $this->session->start();
    $this->session->set('key', 'value');
    $oldId = $this->session->getId();

    $this->session->invalidate();

    expect($this->session->getId())->not->toBe($oldId);
});

test('session stores complex data types', function () {
    $this->session->start();

    $this->session->set('array', ['a' => 1, 'b' => 2]);
    expect($this->session->get('array'))->toBe(['a' => 1, 'b' => 2]);

    $this->session->set('nested', ['x' => ['y' => 'z']]);
    expect($this->session->get('nested'))->toBe(['x' => ['y' => 'z']]);
});
