<?php

declare(strict_types=1);

namespace Eymen\Database;

/**
 * Simple connection pool managing named database connections.
 *
 * Supports lazy connection creation -- connections are only instantiated
 * when first requested. Multiple named configurations can be registered,
 * with one designated as the default.
 */
final class ConnectionPool
{
    /** @var array<string, Connection> Active connection instances */
    private array $connections = [];

    /** @var array<string, array<string, mixed>> Registered configurations */
    private array $configs = [];

    /** @var string Name of the default connection */
    private string $default = 'default';

    /**
     * Register a named connection configuration.
     *
     * Does not create the connection immediately -- it will be created
     * lazily on first access via connection().
     *
     * @param string $name Connection name
     * @param array<string, mixed> $config Connection configuration array
     */
    public function addConfig(string $name, array $config): void
    {
        $this->configs[$name] = $config;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name The connection name to use as default
     *
     * @throws \InvalidArgumentException If the named config does not exist
     */
    public function setDefault(string $name): void
    {
        if (!isset($this->configs[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Connection configuration "%s" has not been registered.', $name)
            );
        }

        $this->default = $name;
    }

    /**
     * Get a connection by name, creating it lazily if needed.
     *
     * @param string|null $name Connection name, or null for default
     * @return Connection The database connection
     *
     * @throws \InvalidArgumentException If no config exists for the name
     */
    public function connection(?string $name = null): Connection
    {
        $name ??= $this->default;

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (!isset($this->configs[$name])) {
            throw new \InvalidArgumentException(
                sprintf('No database configuration found for connection "%s".', $name)
            );
        }

        $this->connections[$name] = new Connection($this->configs[$name]);

        return $this->connections[$name];
    }

    /**
     * Purge (disconnect and remove) a connection.
     *
     * If no name is provided, the default connection is purged.
     *
     * @param string|null $name Connection name, or null for default
     */
    public function purge(?string $name = null): void
    {
        $name ??= $this->default;

        unset($this->connections[$name]);
    }

    /**
     * Get the name of the default connection.
     */
    public function getDefaultName(): string
    {
        return $this->default;
    }
}
