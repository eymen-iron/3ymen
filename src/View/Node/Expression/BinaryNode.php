<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class BinaryNode extends Node
{
    private string $operator;
    private Node $left;
    private Node $right;

    public function __construct(string $operator, Node $left, Node $right, int $line = 0)
    {
        parent::__construct($line);
        $this->operator = $operator;
        $this->left = $left;
        $this->right = $right;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getLeft(): Node
    {
        return $this->left;
    }

    public function getRight(): Node
    {
        return $this->right;
    }
}
