<?php

declare(strict_types=1);

namespace Eymen\View\Node;

abstract class Node
{
    protected int $line;

    public function __construct(int $line = 0)
    {
        $this->line = $line;
    }

    public function getLine(): int
    {
        return $this->line;
    }
}
