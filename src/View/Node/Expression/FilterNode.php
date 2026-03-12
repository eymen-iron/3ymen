<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class FilterNode extends Node
{
    private Node $node;
    private string $name;

    /** @var Node[] */
    private array $arguments;

    /**
     * @param Node[] $arguments
     */
    public function __construct(Node $node, string $name, array $arguments = [], int $line = 0)
    {
        parent::__construct($line);
        $this->node = $node;
        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Node[] */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
