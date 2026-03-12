<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class PrintNode extends Node
{
    private Node $expression;

    public function __construct(Node $expression, int $line = 0)
    {
        parent::__construct($line);
        $this->expression = $expression;
    }

    public function getExpression(): Node
    {
        return $this->expression;
    }
}
