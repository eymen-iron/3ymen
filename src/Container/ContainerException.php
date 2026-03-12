<?php

declare(strict_types=1);

namespace Eymen\Container;

/**
 * Base exception for container errors.
 *
 * Thrown when the container encounters an error during resolution,
 * binding, or auto-wiring operations.
 */
class ContainerException extends \RuntimeException
{
}
