<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class ModuleNode extends Node
{
    /** @var Node[] */
    private array $body;

    /** @var BlockNode[] */
    private array $blocks;

    private ?ExtendsNode $extends;

    /**
     * @param Node[] $body
     * @param BlockNode[] $blocks
     */
    public function __construct(array $body, array $blocks = [], ?ExtendsNode $extends = null, int $line = 0)
    {
        parent::__construct($line);
        $this->body = $body;
        $this->blocks = $blocks;
        $this->extends = $extends;
    }

    /** @return Node[] */
    public function getBody(): array
    {
        return $this->body;
    }

    /** @return BlockNode[] */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getExtends(): ?ExtendsNode
    {
        return $this->extends;
    }

    public function setExtends(ExtendsNode $extends): void
    {
        $this->extends = $extends;
    }

    public function addBlock(BlockNode $block): void
    {
        $this->blocks[$block->getName()] = $block;
    }
}
