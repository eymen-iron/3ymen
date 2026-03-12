<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class BlockNode extends Node
{
    private string $name;

    /** @var Node[] */
    private array $body;

    /**
     * @param Node[] $body
     */
    public function __construct(string $name, array $body, int $line = 0)
    {
        parent::__construct($line);
        $this->name = $name;
        $this->body = $body;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Node[] */
    public function getBody(): array
    {
        return $this->body;
    }
}
