<?php

declare(strict_types=1);

use Eymen\Container\Container;
use Eymen\Container\NotFoundException;

// Basic binding and resolution
test('bind and resolve simple class', function () {
    $container = new Container();
    $container->bind('greeting', fn () => 'Hello World');

    expect($container->make('greeting'))->toBe('Hello World');
});

test('singleton always returns same instance', function () {
    $container = new Container();
    $container->singleton('counter', fn () => new stdClass());

    $a = $container->make('counter');
    $b = $container->make('counter');

    expect($a)->toBe($b);
});

test('instance binds existing object', function () {
    $container = new Container();
    $obj = new stdClass();
    $obj->name = 'test';

    $container->instance('myobj', $obj);

    expect($container->make('myobj'))->toBe($obj);
    expect($container->make('myobj')->name)->toBe('test');
});

test('bind overwrites previous binding', function () {
    $container = new Container();
    $container->bind('val', fn () => 'first');
    $container->bind('val', fn () => 'second');

    expect($container->make('val'))->toBe('second');
});

// PSR-11 compliance
test('has returns true for bound services', function () {
    $container = new Container();
    $container->bind('exists', fn () => true);

    expect($container->has('exists'))->toBeTrue();
});

test('has returns false for non-existent non-class string', function () {
    $container = new Container();
    expect($container->has('totally_nonexistent_service_xyz'))->toBeFalse();
});

test('get resolves same as make', function () {
    $container = new Container();
    $container->bind('service', fn () => 'value');

    expect($container->get('service'))->toBe('value');
});

// Auto-wiring
test('auto-wire resolves class with no dependencies', function () {
    $container = new Container();
    $obj = $container->make(SimpleService::class);
    expect($obj)->toBeInstanceOf(SimpleService::class);
});

test('auto-wire resolves class with typed dependencies', function () {
    $container = new Container();
    $obj = $container->make(ServiceWithDependency::class);
    expect($obj)->toBeInstanceOf(ServiceWithDependency::class);
    expect($obj->dep)->toBeInstanceOf(SimpleService::class);
});

test('make passes parameters to constructor', function () {
    $container = new Container();
    $obj = $container->make(ServiceWithParam::class, ['name' => 'Eymen']);
    expect($obj->name)->toBe('Eymen');
});

// Interface binding
test('bind interface to concrete class', function () {
    $container = new Container();
    $container->bind(TestInterface::class, TestImplementation::class);

    $obj = $container->make(TestInterface::class);
    expect($obj)->toBeInstanceOf(TestImplementation::class);
});

// Contextual binding
test('contextual binding provides different implementations', function () {
    $container = new Container();
    $container->bind(TestInterface::class, TestImplementation::class);
    $container->addContextualBinding(
        ServiceNeedingInterface::class,
        TestInterface::class,
        AlternativeImplementation::class
    );

    $default = $container->make(TestInterface::class);
    expect($default)->toBeInstanceOf(TestImplementation::class);

    $contextual = $container->make(ServiceNeedingInterface::class);
    expect($contextual->dep)->toBeInstanceOf(AlternativeImplementation::class);
});

// Call method
test('call invokes callable with dependency injection', function () {
    $container = new Container();
    $result = $container->call(function (SimpleService $service) {
        return get_class($service);
    });
    expect($result)->toBe(SimpleService::class);
});

test('call with parameters', function () {
    $container = new Container();
    $result = $container->call(function (string $name) {
        return "Hello {$name}";
    }, ['name' => 'Eymen']);
    expect($result)->toBe('Hello Eymen');
});

// Flush
test('flush clears all bindings', function () {
    $container = new Container();
    $container->bind('a', fn () => 1);
    $container->singleton('b', fn () => 2);

    $container->flush();

    expect($container->has('a'))->toBeFalse();
});

test('isShared checks if binding is singleton', function () {
    $container = new Container();
    $container->bind('normal', fn () => 1);
    $container->singleton('shared', fn () => 1);

    expect($container->isShared('shared'))->toBeTrue();
    expect($container->isShared('normal'))->toBeFalse();
});

// Test helper classes
class SimpleService
{
    public string $value = 'simple';
}

class ServiceWithDependency
{
    public function __construct(public SimpleService $dep) {}
}

class ServiceWithParam
{
    public function __construct(public string $name) {}
}

interface TestInterface
{
    public function getValue(): string;
}

class TestImplementation implements TestInterface
{
    public function getValue(): string { return 'default'; }
}

class AlternativeImplementation implements TestInterface
{
    public function getValue(): string { return 'alternative'; }
}

class ServiceNeedingInterface
{
    public function __construct(public TestInterface $dep) {}
}
