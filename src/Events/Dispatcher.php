<?php

declare(strict_types=1);

namespace Eymen\Events;

final class Dispatcher
{
    /** @var array<string, array<\Closure|string|array>> */
    private array $listeners = [];

    /** @var array<string, array<\Closure>> */
    private array $wildcardListeners = [];

    /** @var string[] */
    private array $subscribers = [];

    public function listen(string $event, \Closure|string|array $listener): void
    {
        if (str_contains($event, '*')) {
            $this->wildcardListeners[$event][] = $this->makeListener($listener);
            return;
        }

        $this->listeners[$event][] = $listener;
    }

    public function subscribe(string|object $subscriber): void
    {
        $instance = is_string($subscriber) ? new $subscriber() : $subscriber;

        if (!method_exists($instance, 'subscribe')) {
            throw new \InvalidArgumentException(
                'Subscriber must have a subscribe() method'
            );
        }

        $instance->subscribe($this);
    }

    public function dispatch(object|string $event, mixed $payload = null): mixed
    {
        if (is_object($event)) {
            $eventName = $event::class;
            $payload = $event;
        } else {
            $eventName = $event;
        }

        $result = null;

        foreach ($this->getListeners($eventName) as $listener) {
            $response = $this->callListener($listener, $payload, $eventName);

            if ($response !== null) {
                $result = $response;
            }

            if ($response === false) {
                break;
            }
        }

        return $result;
    }

    public function hasListeners(string $event): bool
    {
        if (isset($this->listeners[$event]) && !empty($this->listeners[$event])) {
            return true;
        }

        foreach ($this->wildcardListeners as $pattern => $listeners) {
            if ($this->matchesWildcard($pattern, $event) && !empty($listeners)) {
                return true;
            }
        }

        return false;
    }

    public function forget(string $event): void
    {
        if (str_contains($event, '*')) {
            unset($this->wildcardListeners[$event]);
        } else {
            unset($this->listeners[$event]);
        }
    }

    public function forgetAll(): void
    {
        $this->listeners = [];
        $this->wildcardListeners = [];
    }

    public function getListeners(string $event): array
    {
        $listeners = [];

        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listeners[] = $this->makeListener($listener);
            }
        }

        foreach ($this->wildcardListeners as $pattern => $wildcardListeners) {
            if ($this->matchesWildcard($pattern, $event)) {
                foreach ($wildcardListeners as $listener) {
                    $listeners[] = $listener;
                }
            }
        }

        return $listeners;
    }

    private function callListener(\Closure $listener, mixed $payload, string $eventName): mixed
    {
        return $listener($payload, $eventName);
    }

    private function makeListener(\Closure|string|array $listener): \Closure
    {
        if ($listener instanceof \Closure) {
            return $listener;
        }

        if (is_string($listener)) {
            return function (mixed $payload, string $event) use ($listener) {
                $instance = new $listener();

                if (method_exists($instance, 'handle')) {
                    return $instance->handle($payload, $event);
                }

                if (is_callable($instance)) {
                    return $instance($payload, $event);
                }

                throw new \RuntimeException("Listener {$listener} has no handle() method");
            };
        }

        if (is_array($listener) && count($listener) === 2) {
            return function (mixed $payload, string $event) use ($listener) {
                [$class, $method] = $listener;
                $instance = is_string($class) ? new $class() : $class;

                return $instance->$method($payload, $event);
            };
        }

        throw new \InvalidArgumentException('Invalid listener format');
    }

    private function matchesWildcard(string $pattern, string $event): bool
    {
        $regex = str_replace(
            ['\\*', '\\?'],
            ['[^.]*', '[^.]'],
            preg_quote($pattern, '/')
        );

        return (bool) preg_match('/^' . $regex . '$/', $event);
    }
}
