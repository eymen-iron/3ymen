<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class TextNode extends Node
{
    private string $data;

    public function __construct(string $data, int $line = 0)
    {
        parent::__construct($line);
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
