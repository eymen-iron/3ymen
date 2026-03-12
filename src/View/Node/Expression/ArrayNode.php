<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class ArrayNode extends Node
{
    /** @var array{key: Node, value: Node}[] */
    private array $elements;

    /**
     * @param array{key: Node, value: Node}[] $elements
     */
    public function __construct(array $elements = [], int $line = 0)
    {
        parent::__construct($line);
        $this->elements = $elements;
    }

    /** @return array{key: Node, value: Node}[] */
    public function getElements(): array
    {
        return $this->elements;
    }

    public function addElement(Node $key, Node $value): void
    {
        $this->elements[] = ['key' => $key, 'value' => $value];
    }
}
