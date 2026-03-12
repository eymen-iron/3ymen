<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class ForNode extends Node
{
    private Node $keyTarget;
    private Node $valueTarget;
    private Node $sequence;

    /** @var Node[] */
    private array $body;

    /** @var Node[] */
    private array $elseBody;

    private bool $hasKey;

    /**
     * @param Node[] $body
     * @param Node[] $elseBody
     */
    public function __construct(
        Node $valueTarget,
        Node $sequence,
        array $body,
        array $elseBody = [],
        ?Node $keyTarget = null,
        int $line = 0,
    ) {
        parent::__construct($line);
        $this->valueTarget = $valueTarget;
        $this->sequence = $sequence;
        $this->body = $body;
        $this->elseBody = $elseBody;
        $this->keyTarget = $keyTarget ?? new Expression\NameNode('_key', $line);
        $this->hasKey = $keyTarget !== null;
    }

    public function getKeyTarget(): Node
    {
        return $this->keyTarget;
    }

    public function getValueTarget(): Node
    {
        return $this->valueTarget;
    }

    public function getSequence(): Node
    {
        return $this->sequence;
    }

    /** @return Node[] */
    public function getBody(): array
    {
        return $this->body;
    }

    /** @return Node[] */
    public function getElseBody(): array
    {
        return $this->elseBody;
    }

    public function hasKey(): bool
    {
        return $this->hasKey;
    }

    public function hasElse(): bool
    {
        return count($this->elseBody) > 0;
    }
}
