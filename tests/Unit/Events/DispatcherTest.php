<?php

declare(strict_types=1);

use Eymen\Events\Dispatcher;
use Eymen\Events\EventInterface;

beforeEach(function () {
    $this->dispatcher = new Dispatcher();
});

test('listen and dispatch event by string', function () {
    $received = null;

    // Listener signature: ($payload, $eventName)
    $this->dispatcher->listen('user.created', function ($payload, $eventName) use (&$received) {
        $received = $payload;
    });

    $this->dispatcher->dispatch('user.created', ['name' => 'Eymen']);

    expect($received)->toBe(['name' => 'Eymen']);
});

test('listen and dispatch event object', function () {
    $event = new class implements EventInterface {
        public string $name = 'TestEvent';
    };

    $receivedEvent = null;
    $eventClass = get_class($event);

    $this->dispatcher->listen($eventClass, function ($e) use (&$receivedEvent) {
        $receivedEvent = $e;
    });

    $this->dispatcher->dispatch($event);

    expect($receivedEvent)->not->toBeNull();
    expect($receivedEvent->name)->toBe('TestEvent');
});

test('multiple listeners receive same event', function () {
    $count = 0;

    $this->dispatcher->listen('ping', function () use (&$count) { $count++; });
    $this->dispatcher->listen('ping', function () use (&$count) { $count++; });
    $this->dispatcher->listen('ping', function () use (&$count) { $count++; });

    $this->dispatcher->dispatch('ping');

    expect($count)->toBe(3);
});

test('wildcard listener matches pattern', function () {
    $count = 0;

    $this->dispatcher->listen('user.*', function () use (&$count) {
        $count++;
    });

    $this->dispatcher->dispatch('user.created');
    $this->dispatcher->dispatch('user.updated');
    $this->dispatcher->dispatch('user.deleted');
    $this->dispatcher->dispatch('post.created'); // should NOT match

    expect($count)->toBe(3);
});

test('hasListeners checks for registered listeners', function () {
    $this->dispatcher->listen('exists', fn () => null);

    expect($this->dispatcher->hasListeners('exists'))->toBeTrue();
    expect($this->dispatcher->hasListeners('missing'))->toBeFalse();
});

test('forget removes all listeners for event', function () {
    $this->dispatcher->listen('temp', fn () => null);
    expect($this->dispatcher->hasListeners('temp'))->toBeTrue();

    $this->dispatcher->forget('temp');
    expect($this->dispatcher->hasListeners('temp'))->toBeFalse();
});

test('forgetAll clears all listeners', function () {
    $this->dispatcher->listen('a', fn () => null);
    $this->dispatcher->listen('b', fn () => null);

    $this->dispatcher->forgetAll();

    expect($this->dispatcher->hasListeners('a'))->toBeFalse();
    expect($this->dispatcher->hasListeners('b'))->toBeFalse();
});

test('getListeners returns listeners for event', function () {
    $this->dispatcher->listen('test', fn () => 'first');
    $this->dispatcher->listen('test', fn () => 'second');

    $listeners = $this->dispatcher->getListeners('test');
    expect($listeners)->toHaveCount(2);
});

test('dispatch with no listeners does not error', function () {
    $this->dispatcher->dispatch('no_listeners');
    expect(true)->toBeTrue();
});

test('listeners receive correct event name for wildcards', function () {
    $count = 0;

    $this->dispatcher->listen('order.*', function () use (&$count) {
        $count++;
    });

    $this->dispatcher->dispatch('order.placed', ['id' => 1]);
    $this->dispatcher->dispatch('order.shipped', ['id' => 2]);

    expect($count)->toBe(2);
});
