<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class IfNode extends Node
{
    /** @var array{condition: Node, body: Node[]}[] */
    private array $branches;

    /** @var Node[] */
    private array $elseBody;

    /**
     * @param array{condition: Node, body: Node[]}[] $branches
     * @param Node[] $elseBody
     */
    public function __construct(array $branches, array $elseBody = [], int $line = 0)
    {
        parent::__construct($line);
        $this->branches = $branches;
        $this->elseBody = $elseBody;
    }

    /** @return array{condition: Node, body: Node[]}[] */
    public function getBranches(): array
    {
        return $this->branches;
    }

    /** @return Node[] */
    public function getElseBody(): array
    {
        return $this->elseBody;
    }

    public function hasElse(): bool
    {
        return count($this->elseBody) > 0;
    }
}
