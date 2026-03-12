<?php

declare(strict_types=1);

namespace Eymen\View\Node\Expression;

use Eymen\View\Node\Node;

final class NameNode extends Node
{
    private string $name;

    public function __construct(string $name, int $line = 0)
    {
        parent::__construct($line);
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
