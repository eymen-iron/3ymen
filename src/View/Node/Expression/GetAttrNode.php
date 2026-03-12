<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class GetAttrNode extends Node
{
    private Node $node;
    private Node $attribute;

    public function __construct(Node $node, Node $attribute, int $line = 0)
    {
        parent::__construct($line);
        $this->node = $node;
        $this->attribute = $attribute;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function getAttribute(): Node
    {
        return $this->attribute;
    }
}
