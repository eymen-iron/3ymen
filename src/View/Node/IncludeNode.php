<?php

declare(strict_types=1);

namespace Eymen\View\Node;

final class IncludeNode extends Node
{
    private string $template;

    public function __construct(string $template, int $line = 0)
    {
        parent::__construct($line);
        $this->template = $template;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
