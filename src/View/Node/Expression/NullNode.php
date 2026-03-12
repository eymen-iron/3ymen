<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class NullNode extends Node
{
    public function __construct(int $line = 0)
    {
        parent::__construct($line);
    }
}
