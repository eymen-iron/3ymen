<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class StringNode extends Node
{
    private string $value;

    public function __construct(string $value, int $line = 0)
    {
        parent::__construct($line);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
