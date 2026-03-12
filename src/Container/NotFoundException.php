<?php

declare(strict_types=1);

namespace Eymen\Container;

/**
 * Exception thrown when a requested entry is not found in the container.
 *
 * PSR-11 compatible: represents a "not found" condition during resolution.
 */
class NotFoundException extends ContainerException
{
}
