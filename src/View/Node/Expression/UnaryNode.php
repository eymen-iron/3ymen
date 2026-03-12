<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class UnaryNode extends Node
{
    private string $operator;
    private Node $operand;

    public function __construct(string $operator, Node $operand, int $line = 0)
    {
        parent::__construct($line);
        $this->operator = $operator;
        $this->operand = $operand;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getOperand(): Node
    {
        return $this->operand;
    }
}
