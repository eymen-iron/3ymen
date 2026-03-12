<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class ExtendsNode extends Node
{
    private string $parent;

    public function __construct(string $parent, int $line = 0)
    {
        parent::__construct($line);
        $this->parent = $parent;
    }

    public function getParent(): string
    {
        return $this->parent;
    }
}
