<?php

declare(strict_types=1);

namespace Eymen\View;

final class TokenStream
{
    /** @var Token[] */
    private array $tokens;
    private int $current = 0;
    private string $source;

    /**
     * @param Token[] $tokens
     */
    public function __construct(array $tokens, string $source = '')
    {
        $this->tokens = $tokens;
        $this->source = $source;
    }

    public function next(): Token
    {
        if (!isset($this->tokens[$this->current])) {
            throw new \RuntimeException('Unexpected end of template.');
        }

        $token = $this->tokens[$this->current];
        $this->current++;

        return $token;
    }

    public function current(): Token
    {
        if (!isset($this->tokens[$this->current])) {
            throw new \RuntimeException('Unexpected end of template.');
        }

        return $this->tokens[$this->current];
    }

    /**
     * @throws \RuntimeException
     */
    public function expect(int $type, ?string $value = null): Token
    {
        $token = $this->current();

        if (!$token->test($type, $value)) {
            $message = sprintf(
                'Unexpected token "%s" of value "%s" ("%s" expected%s) at line %d in "%s".',
                $token->getTypeName(),
                $token->value,
                Token::class . '::' . $this->getTypeName($type),
                $value !== null ? sprintf(' with value "%s"', $value) : '',
                $token->line,
                $this->source,
            );
            throw new \RuntimeException($message);
        }

        $this->next();

        return $token;
    }

    public function test(int $type, ?string $value = null): bool
    {
        return $this->current()->test($type, $value);
    }

    public function look(int $offset = 1): Token
    {
        $index = $this->current + $offset;

        if (!isset($this->tokens[$index])) {
            return new Token(Token::EOF, '', 0);
        }

        return $this->tokens[$index];
    }

    public function isEOF(): bool
    {
        return $this->current()->type === Token::EOF;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCurrent(): int
    {
        return $this->current;
    }

    private function getTypeName(int $type): string
    {
        $names = [
            Token::TEXT => 'TEXT',
            Token::VAR_START => 'VAR_START',
            Token::VAR_END => 'VAR_END',
            Token::BLOCK_START => 'BLOCK_START',
            Token::BLOCK_END => 'BLOCK_END',
            Token::NAME => 'NAME',
            Token::STRING => 'STRING',
            Token::NUMBER => 'NUMBER',
            Token::OPERATOR => 'OPERATOR',
            Token::PUNCTUATION => 'PUNCTUATION',
            Token::EOF => 'EOF',
        ];

        return $names[$type] ?? 'UNKNOWN';
    }
}
