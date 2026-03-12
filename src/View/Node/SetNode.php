<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class SetNode extends Node
{
    private string $name;
    private Node $value;

    public function __construct(string $name, Node $value, int $line = 0)
    {
        parent::__construct($line);
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): Node
    {
        return $this->value;
    }
}
