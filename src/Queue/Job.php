<?php

declare(strict_types=1);

namespace Eymen\Queue;

abstract class Job implements \JsonSerializable
{
    public int $tries = 3;
    public int $timeout = 60;
    public int $retryAfter = 60;
    public ?string $queue = null;

    abstract public function handle(): void;

    public function failed(\Throwable $exception): void
    {
        // Override in subclass to handle failure
    }

    public function jsonSerialize(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $properties[$prop->getName()] = $prop->getValue($this);
        }

        return [
            'class' => static::class,
            'properties' => $properties,
            'tries' => $this->tries,
            'timeout' => $this->timeout,
            'retryAfter' => $this->retryAfter,
            'queue' => $this->queue,
        ];
    }

    public static function fromArray(array $data): static
    {
        $class = $data['class'];

        if (!class_exists($class)) {
            throw new \RuntimeException("Job class not found: {$class}");
        }

        $instance = new $class();

        if (isset($data['properties'])) {
            foreach ($data['properties'] as $key => $value) {
                if (property_exists($instance, $key)) {
                    $instance->$key = $value;
                }
            }
        }

        return $instance;
    }

    public function getDisplayName(): string
    {
        return static::class;
    }
}
