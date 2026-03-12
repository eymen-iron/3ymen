<?php

declare(strict_types=1);

namespace Eymen\View;

final class Token
{
    public const TEXT = 0;
    public const VAR_START = 1;
    public const VAR_END = 2;
    public const BLOCK_START = 3;
    public const BLOCK_END = 4;
    public const NAME = 5;
    public const STRING = 6;
    public const NUMBER = 7;
    public const OPERATOR = 8;
    public const PUNCTUATION = 9;
    public const EOF = 10;

    private const TYPE_NAMES = [
        self::TEXT => 'TEXT',
        self::VAR_START => 'VAR_START',
        self::VAR_END => 'VAR_END',
        self::BLOCK_START => 'BLOCK_START',
        self::BLOCK_END => 'BLOCK_END',
        self::NAME => 'NAME',
        self::STRING => 'STRING',
        self::NUMBER => 'NUMBER',
        self::OPERATOR => 'OPERATOR',
        self::PUNCTUATION => 'PUNCTUATION',
        self::EOF => 'EOF',
    ];

    public function __construct(
        public readonly int $type,
        public readonly string $value,
        public readonly int $line,
    ) {
    }

    public function test(int $type, ?string $value = null): bool
    {
        return $this->type === $type && ($value === null || $this->value === $value);
    }

    public function getTypeName(): string
    {
        return self::TYPE_NAMES[$this->type] ?? 'UNKNOWN';
    }

    public function __toString(): string
    {
        return sprintf(
            '%s(%s) at line %d',
            $this->getTypeName(),
            $this->value,
            $this->line,
        );
    }
}
