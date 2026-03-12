<?php

declare(strict_types=1);

namespace Eymen\View;

final class VexLexer
{
    private const STATE_DATA = 0;
    private const STATE_BLOCK = 1;
    private const STATE_VAR = 2;
    private const STATE_COMMENT = 3;

    private const OPERATORS = [
        '==', '!=', '<=', '>=', '&&', '||',
        '+', '-', '*', '/', '%', '=',
        '<', '>', '|', '.',
        'and', 'or', 'not', 'in', 'is',
    ];

    private const PUNCTUATION = ['(', ')', '[', ']', '{', '}', ',', ':', '?'];

    private string $source;
    private string $name;
    private int $cursor;
    private int $end;
    private int $line;
    private int $state;
    /** @var Token[] */
    private array $tokens;
    /** @var int[] */
    private array $brackets;

    public function tokenize(string $source, string $name = ''): TokenStream
    {
        $this->source = $source;
        $this->name = $name;
        $this->cursor = 0;
        $this->end = strlen($source);
        $this->line = 1;
        $this->state = self::STATE_DATA;
        $this->tokens = [];
        $this->brackets = [];

        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;
                case self::STATE_BLOCK:
                    $this->lexBlock();
                    break;
                case self::STATE_VAR:
                    $this->lexVar();
                    break;
                case self::STATE_COMMENT:
                    $this->lexComment();
                    break;
            }
        }

        $this->tokens[] = new Token(Token::EOF, '', $this->line);

        if (count($this->brackets) > 0) {
            throw new \RuntimeException(sprintf(
                'Unclosed bracket in "%s" at line %d.',
                $this->name,
                $this->line,
            ));
        }

        return new TokenStream($this->tokens, $this->name);
    }

    private function lexData(): void
    {
        $pos = $this->findEarliestTag();

        if ($pos === false) {
            // No more tags, rest is text
            $text = substr($this->source, $this->cursor);
            if ($text !== '') {
                $this->pushToken(Token::TEXT, $text);
                $this->moveCursor($text);
            }
            $this->cursor = $this->end;
            return;
        }

        // Text before the tag
        $text = substr($this->source, $this->cursor, $pos - $this->cursor);
        if ($text !== '') {
            $this->pushToken(Token::TEXT, $text);
            $this->moveCursor($text);
        }

        $tag = substr($this->source, $this->cursor, 2);

        match ($tag) {
            '{{' => $this->enterVar(),
            '{%' => $this->enterBlock(),
            '{#' => $this->enterComment(),
            default => throw new \RuntimeException(sprintf(
                'Unexpected tag "%s" at line %d in "%s".',
                $tag,
                $this->line,
                $this->name,
            )),
        };
    }

    private function findEarliestTag(): int|false
    {
        $positions = [];

        $varPos = strpos($this->source, '{{', $this->cursor);
        if ($varPos !== false) {
            $positions[] = $varPos;
        }

        $blockPos = strpos($this->source, '{%', $this->cursor);
        if ($blockPos !== false) {
            $positions[] = $blockPos;
        }

        $commentPos = strpos($this->source, '{#', $this->cursor);
        if ($commentPos !== false) {
            $positions[] = $commentPos;
        }

        if (count($positions) === 0) {
            return false;
        }

        return min($positions);
    }

    private function enterVar(): void
    {
        $this->pushToken(Token::VAR_START, '{{');
        $this->cursor += 2;
        $this->state = self::STATE_VAR;
    }

    private function enterBlock(): void
    {
        $this->pushToken(Token::BLOCK_START, '{%');
        $this->cursor += 2;
        $this->state = self::STATE_BLOCK;
    }

    private function enterComment(): void
    {
        $this->cursor += 2;
        $this->state = self::STATE_COMMENT;
    }

    private function lexComment(): void
    {
        $end = strpos($this->source, '#}', $this->cursor);

        if ($end === false) {
            throw new \RuntimeException(sprintf(
                'Unclosed comment in "%s" at line %d.',
                $this->name,
                $this->line,
            ));
        }

        // Count newlines in the comment
        $comment = substr($this->source, $this->cursor, $end - $this->cursor);
        $this->line += substr_count($comment, "\n");
        $this->cursor = $end + 2;
        $this->state = self::STATE_DATA;
    }

    private function lexVar(): void
    {
        $this->skipWhitespace();

        if ($this->cursor >= $this->end) {
            throw new \RuntimeException(sprintf(
                'Unclosed variable block in "%s" at line %d.',
                $this->name,
                $this->line,
            ));
        }

        // Check for end of variable
        if (substr($this->source, $this->cursor, 2) === '}}') {
            $this->pushToken(Token::VAR_END, '}}');
            $this->cursor += 2;
            $this->state = self::STATE_DATA;
            return;
        }

        $this->lexExpression();
    }

    private function lexBlock(): void
    {
        $this->skipWhitespace();

        if ($this->cursor >= $this->end) {
            throw new \RuntimeException(sprintf(
                'Unclosed block tag in "%s" at line %d.',
                $this->name,
                $this->line,
            ));
        }

        // Check for end of block
        if (substr($this->source, $this->cursor, 2) === '%}') {
            $this->pushToken(Token::BLOCK_END, '%}');
            $this->cursor += 2;
            $this->state = self::STATE_DATA;
            return;
        }

        $this->lexExpression();
    }

    private function lexExpression(): void
    {
        $this->skipWhitespace();

        if ($this->cursor >= $this->end) {
            return;
        }

        $char = $this->source[$this->cursor];

        // String literal
        if ($char === '"' || $char === "'") {
            $this->lexString($char);
            return;
        }

        // Number literal
        if (ctype_digit($char) || ($char === '.' && $this->cursor + 1 < $this->end && ctype_digit($this->source[$this->cursor + 1]))) {
            $this->lexNumber();
            return;
        }

        // Two-character operators (check before single character)
        if ($this->cursor + 1 < $this->end) {
            $twoChar = substr($this->source, $this->cursor, 2);
            if (in_array($twoChar, ['==', '!=', '<=', '>=', '&&', '||'], true)) {
                $this->pushToken(Token::OPERATOR, $twoChar);
                $this->cursor += 2;
                return;
            }
        }

        // Punctuation
        if (in_array($char, self::PUNCTUATION, true)) {
            if ($char === '(' || $char === '[' || $char === '{') {
                $this->brackets[] = $this->line;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                if (count($this->brackets) === 0) {
                    throw new \RuntimeException(sprintf(
                        'Unexpected closing bracket "%s" at line %d in "%s".',
                        $char,
                        $this->line,
                        $this->name,
                    ));
                }
                array_pop($this->brackets);
            }
            $this->pushToken(Token::PUNCTUATION, $char);
            $this->cursor++;
            return;
        }

        // Single-character operators
        if (in_array($char, ['+', '-', '*', '/', '%', '|', '.', '<', '>', '='], true)) {
            $this->pushToken(Token::OPERATOR, $char);
            $this->cursor++;
            return;
        }

        // Name or keyword operator (and, or, not, in, is, true, false, null)
        if (ctype_alpha($char) || $char === '_') {
            $this->lexName();
            return;
        }

        throw new \RuntimeException(sprintf(
            'Unexpected character "%s" at line %d in "%s".',
            $char,
            $this->line,
            $this->name,
        ));
    }

    private function lexString(string $quote): void
    {
        $this->cursor++; // Skip opening quote
        $value = '';
        $escaped = false;

        while ($this->cursor < $this->end) {
            $char = $this->source[$this->cursor];

            if ($escaped) {
                $value .= match ($char) {
                    'n' => "\n",
                    't' => "\t",
                    'r' => "\r",
                    '\\' => '\\',
                    $quote => $quote,
                    default => '\\' . $char,
                };
                $escaped = false;
                $this->cursor++;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $this->cursor++;
                continue;
            }

            if ($char === $quote) {
                $this->cursor++; // Skip closing quote
                $this->pushToken(Token::STRING, $value);
                return;
            }

            if ($char === "\n") {
                $this->line++;
            }

            $value .= $char;
            $this->cursor++;
        }

        throw new \RuntimeException(sprintf(
            'Unclosed string in "%s" at line %d.',
            $this->name,
            $this->line,
        ));
    }

    private function lexNumber(): void
    {
        $start = $this->cursor;
        $isFloat = false;

        while ($this->cursor < $this->end && (ctype_digit($this->source[$this->cursor]) || $this->source[$this->cursor] === '.')) {
            if ($this->source[$this->cursor] === '.') {
                if ($isFloat) {
                    break; // Second dot, stop
                }
                // Check if the next char is a digit, otherwise it's a property accessor
                if ($this->cursor + 1 < $this->end && ctype_digit($this->source[$this->cursor + 1])) {
                    $isFloat = true;
                } else {
                    break;
                }
            }
            $this->cursor++;
        }

        $number = substr($this->source, $start, $this->cursor - $start);
        $this->pushToken(Token::NUMBER, $number);
    }

    private function lexName(): void
    {
        $start = $this->cursor;

        while ($this->cursor < $this->end && (ctype_alnum($this->source[$this->cursor]) || $this->source[$this->cursor] === '_')) {
            $this->cursor++;
        }

        $name = substr($this->source, $start, $this->cursor - $start);

        // Keyword operators
        if (in_array($name, ['and', 'or', 'not', 'in', 'is'], true)) {
            $this->pushToken(Token::OPERATOR, $name);
        } else {
            $this->pushToken(Token::NAME, $name);
        }
    }

    private function skipWhitespace(): void
    {
        while ($this->cursor < $this->end && ctype_space($this->source[$this->cursor])) {
            if ($this->source[$this->cursor] === "\n") {
                $this->line++;
            }
            $this->cursor++;
        }
    }

    private function pushToken(int $type, string $value): void
    {
        // Avoid empty text tokens
        if ($type === Token::TEXT && $value === '') {
            return;
        }

        $this->tokens[] = new Token($type, $value, $this->line);
    }

    private function moveCursor(string $text): void
    {
        $this->line += substr_count($text, "\n");
        $this->cursor += strlen($text);
    }
}
