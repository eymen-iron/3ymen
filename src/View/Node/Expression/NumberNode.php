<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class NumberNode extends Node
{
    private int|float $value;

    public function __construct(int|float $value, int $line = 0)
    {
        parent::__construct($line);
        $this->value = $value;
    }

    public function getValue(): int|float
    {
        return $this->value;
    }
}
