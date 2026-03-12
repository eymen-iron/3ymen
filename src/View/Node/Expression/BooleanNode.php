<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class BooleanNode extends Node
{
    private bool $value;

    public function __construct(bool $value, int $line = 0)
    {
        parent::__construct($line);
        $this->value = $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
