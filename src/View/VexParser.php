<?php

declare(strict_types=1);

namespace Eymen\View;

use Eymen\View\Node\BlockNode;
use Eymen\View\Node\ExtendsNode;
use Eymen\View\Node\ForNode;
use Eymen\View\Node\IfNode;
use Eymen\View\Node\IncludeNode;
use Eymen\View\Node\ModuleNode;
use Eymen\View\Node\Node;
use Eymen\View\Node\PrintNode;
use Eymen\View\Node\SetNode;
use Eymen\View\Node\TextNode;
use Eymen\View\Node\Expression\ArrayNode;
use Eymen\View\Node\Expression\BinaryNode;
use Eymen\View\Node\Expression\BooleanNode;
use Eymen\View\Node\Expression\FilterNode;
use Eymen\View\Node\Expression\GetAttrNode;
use Eymen\View\Node\Expression\NameNode;
use Eymen\View\Node\Expression\NullNode;
use Eymen\View\Node\Expression\NumberNode;
use Eymen\View\Node\Expression\StringNode;
use Eymen\View\Node\Expression\UnaryNode;

final class VexParser
{
    private TokenStream $stream;

    /** @var BlockNode[] */
    private array $blocks = [];

    private ?ExtendsNode $extends = null;

    /**
     * Operator precedence table (higher = tighter binding).
     */
    private const BINARY_OPERATORS = [
        'or'  => ['precedence' => 10, 'associativity' => 'left'],
        '||'  => ['precedence' => 10, 'associativity' => 'left'],
        'and' => ['precedence' => 15, 'associativity' => 'left'],
        '&&'  => ['precedence' => 15, 'associativity' => 'left'],
        '=='  => ['precedence' => 20, 'associativity' => 'left'],
        '!='  => ['precedence' => 20, 'associativity' => 'left'],
        '<'   => ['precedence' => 20, 'associativity' => 'left'],
        '>'   => ['precedence' => 20, 'associativity' => 'left'],
        '<='  => ['precedence' => 20, 'associativity' => 'left'],
        '>='  => ['precedence' => 20, 'associativity' => 'left'],
        'in'  => ['precedence' => 20, 'associativity' => 'left'],
        'is'  => ['precedence' => 20, 'associativity' => 'left'],
        '+'   => ['precedence' => 30, 'associativity' => 'left'],
        '-'   => ['precedence' => 30, 'associativity' => 'left'],
        '~'   => ['precedence' => 40, 'associativity' => 'left'],
        '*'   => ['precedence' => 60, 'associativity' => 'left'],
        '/'   => ['precedence' => 60, 'associativity' => 'left'],
        '%'   => ['precedence' => 60, 'associativity' => 'left'],
    ];

    public function parse(TokenStream $stream): ModuleNode
    {
        $this->stream = $stream;
        $this->blocks = [];
        $this->extends = null;

        $body = $this->parseBody();

        return new ModuleNode($body, $this->blocks, $this->extends, 1);
    }

    /**
     * Parse a list of nodes until one of the end tokens is found.
     *
     * @param string[] $endTokens Block tag names that terminate this body (e.g. ['endblock', 'else'])
     * @return Node[]
     */
    private function parseBody(array $endTokens = []): array
    {
        $nodes = [];

        while (!$this->stream->isEOF()) {
            $token = $this->stream->current();

            switch ($token->type) {
                case Token::TEXT:
                    $this->stream->next();
                    $nodes[] = new TextNode($token->value, $token->line);
                    break;

                case Token::VAR_START:
                    $nodes[] = $this->parseVariable();
                    break;

                case Token::BLOCK_START:
                    // Peek at the block tag name
                    $nextToken = $this->stream->look(1);

                    // Check if this is an end token we're looking for
                    if ($nextToken->type === Token::NAME && in_array($nextToken->value, $endTokens, true)) {
                        return $nodes;
                    }

                    $nodes[] = $this->parseBlockTag();
                    break;

                default:
                    throw new \RuntimeException(sprintf(
                        'Unexpected token "%s" at line %d.',
                        $token->getTypeName(),
                        $token->line,
                    ));
            }
        }

        if (count($endTokens) > 0) {
            throw new \RuntimeException(sprintf(
                'Unexpected end of template. Expected one of: %s.',
                implode(', ', $endTokens),
            ));
        }

        return $nodes;
    }

    private function parseVariable(): PrintNode
    {
        $token = $this->stream->expect(Token::VAR_START);
        $line = $token->line;

        $expr = $this->parseExpression();

        $this->stream->expect(Token::VAR_END);

        return new PrintNode($expr, $line);
    }

    private function parseBlockTag(): Node
    {
        $this->stream->expect(Token::BLOCK_START);
        $tag = $this->stream->expect(Token::NAME);

        $node = match ($tag->value) {
            'block' => $this->parseBlock(),
            'extends' => $this->parseExtends(),
            'include' => $this->parseInclude(),
            'for' => $this->parseFor(),
            'if' => $this->parseIf(),
            'set' => $this->parseSet(),
            default => throw new \RuntimeException(sprintf(
                'Unknown block tag "%s" at line %d.',
                $tag->value,
                $tag->line,
            )),
        };

        return $node;
    }

    private function parseBlock(): BlockNode
    {
        $nameToken = $this->stream->expect(Token::NAME);
        $name = $nameToken->value;
        $line = $nameToken->line;

        $this->stream->expect(Token::BLOCK_END);

        $body = $this->parseBody(['endblock']);

        // Consume endblock
        $this->stream->expect(Token::BLOCK_START);
        $this->stream->expect(Token::NAME, 'endblock');
        $this->stream->expect(Token::BLOCK_END);

        $block = new BlockNode($name, $body, $line);
        $this->blocks[$name] = $block;

        return $block;
    }

    private function parseExtends(): ExtendsNode
    {
        $parentToken = $this->stream->expect(Token::STRING);
        $line = $parentToken->line;

        $this->stream->expect(Token::BLOCK_END);

        $extends = new ExtendsNode($parentToken->value, $line);
        $this->extends = $extends;

        return $extends;
    }

    private function parseInclude(): IncludeNode
    {
        $templateToken = $this->stream->expect(Token::STRING);
        $line = $templateToken->line;

        $this->stream->expect(Token::BLOCK_END);

        return new IncludeNode($templateToken->value, $line);
    }

    private function parseFor(): ForNode
    {
        $line = $this->stream->current()->line;

        // Parse: key, value in sequence  OR  value in sequence
        $target = $this->stream->expect(Token::NAME);
        $keyTarget = null;
        $valueTarget = new NameNode($target->value, $target->line);

        // Check for key, value syntax
        if ($this->stream->test(Token::PUNCTUATION, ',')) {
            $this->stream->next(); // consume comma
            $keyTarget = $valueTarget;
            $valToken = $this->stream->expect(Token::NAME);
            $valueTarget = new NameNode($valToken->value, $valToken->line);
        }

        $this->stream->expect(Token::OPERATOR, 'in');

        $sequence = $this->parseExpression();

        $this->stream->expect(Token::BLOCK_END);

        $body = $this->parseBody(['endfor', 'else']);

        $elseBody = [];
        if ($this->stream->look(1)->test(Token::NAME, 'else')) {
            $this->stream->expect(Token::BLOCK_START);
            $this->stream->expect(Token::NAME, 'else');
            $this->stream->expect(Token::BLOCK_END);
            $elseBody = $this->parseBody(['endfor']);
        }

        // Consume endfor
        $this->stream->expect(Token::BLOCK_START);
        $this->stream->expect(Token::NAME, 'endfor');
        $this->stream->expect(Token::BLOCK_END);

        return new ForNode($valueTarget, $sequence, $body, $elseBody, $keyTarget, $line);
    }

    private function parseIf(): IfNode
    {
        $line = $this->stream->current()->line;

        $condition = $this->parseExpression();
        $this->stream->expect(Token::BLOCK_END);

        $body = $this->parseBody(['endif', 'else', 'elseif']);

        $branches = [['condition' => $condition, 'body' => $body]];
        $elseBody = [];

        // Handle elseif and else chains
        while (true) {
            $peekTag = $this->stream->look(1);

            if ($peekTag->test(Token::NAME, 'elseif')) {
                $this->stream->expect(Token::BLOCK_START);
                $this->stream->expect(Token::NAME, 'elseif');

                $condition = $this->parseExpression();
                $this->stream->expect(Token::BLOCK_END);

                $body = $this->parseBody(['endif', 'else', 'elseif']);
                $branches[] = ['condition' => $condition, 'body' => $body];
            } elseif ($peekTag->test(Token::NAME, 'else')) {
                $this->stream->expect(Token::BLOCK_START);
                $this->stream->expect(Token::NAME, 'else');
                $this->stream->expect(Token::BLOCK_END);

                $elseBody = $this->parseBody(['endif']);
                break;
            } elseif ($peekTag->test(Token::NAME, 'endif')) {
                break;
            } else {
                throw new \RuntimeException(sprintf(
                    'Unexpected tag "%s" in if block at line %d. Expected elseif, else, or endif.',
                    $peekTag->value,
                    $peekTag->line,
                ));
            }
        }

        // Consume endif
        $this->stream->expect(Token::BLOCK_START);
        $this->stream->expect(Token::NAME, 'endif');
        $this->stream->expect(Token::BLOCK_END);

        return new IfNode($branches, $elseBody, $line);
    }

    private function parseSet(): SetNode
    {
        $nameToken = $this->stream->expect(Token::NAME);
        $line = $nameToken->line;

        $this->stream->expect(Token::OPERATOR, '=');

        $value = $this->parseExpression();

        $this->stream->expect(Token::BLOCK_END);

        return new SetNode($nameToken->value, $value, $line);
    }

    // -----------------------------------------------------------------------
    //  Expression parsing (Pratt / precedence climbing)
    // -----------------------------------------------------------------------

    private function parseExpression(): Node
    {
        $expr = $this->parseUnaryExpression();
        $expr = $this->parseBinaryExpression($expr, 0);
        $expr = $this->parseConditionalExpression($expr);
        $expr = $this->parseFilterExpression($expr);

        return $expr;
    }

    private function parseUnaryExpression(): Node
    {
        $token = $this->stream->current();

        // Unary not
        if ($token->test(Token::OPERATOR, 'not') || $token->test(Token::OPERATOR, '-') || $token->test(Token::OPERATOR, '+')) {
            $this->stream->next();
            $operand = $this->parseUnaryExpression();
            return new UnaryNode($token->value, $operand, $token->line);
        }

        return $this->parsePrimaryExpression();
    }

    private function parsePrimaryExpression(): Node
    {
        $token = $this->stream->current();

        switch ($token->type) {
            case Token::NAME:
                $this->stream->next();
                $node = $this->parseNameExpression($token);
                break;

            case Token::STRING:
                $this->stream->next();
                $node = new StringNode($token->value, $token->line);
                break;

            case Token::NUMBER:
                $this->stream->next();
                $value = str_contains($token->value, '.') ? (float) $token->value : (int) $token->value;
                $node = new NumberNode($value, $token->line);
                break;

            case Token::PUNCTUATION:
                if ($token->value === '(') {
                    $node = $this->parseParenthesizedExpression();
                } elseif ($token->value === '[') {
                    $node = $this->parseArrayExpression();
                } elseif ($token->value === '{') {
                    $node = $this->parseHashExpression();
                } else {
                    throw new \RuntimeException(sprintf(
                        'Unexpected punctuation "%s" at line %d.',
                        $token->value,
                        $token->line,
                    ));
                }
                break;

            default:
                throw new \RuntimeException(sprintf(
                    'Unexpected token "%s" of value "%s" at line %d.',
                    $token->getTypeName(),
                    $token->value,
                    $token->line,
                ));
        }

        // Handle property access and method calls: foo.bar, foo[bar]
        $node = $this->parsePostfixExpression($node);

        return $node;
    }

    private function parseNameExpression(Token $token): Node
    {
        $name = $token->value;

        return match ($name) {
            'true', 'TRUE' => new BooleanNode(true, $token->line),
            'false', 'FALSE' => new BooleanNode(false, $token->line),
            'null', 'NULL', 'none' => new NullNode($token->line),
            default => new NameNode($name, $token->line),
        };
    }

    private function parseParenthesizedExpression(): Node
    {
        $this->stream->expect(Token::PUNCTUATION, '(');
        $expr = $this->parseExpression();
        $this->stream->expect(Token::PUNCTUATION, ')');

        return $expr;
    }

    private function parseArrayExpression(): ArrayNode
    {
        $token = $this->stream->expect(Token::PUNCTUATION, '[');
        $line = $token->line;
        $elements = [];
        $index = 0;

        while (!$this->stream->test(Token::PUNCTUATION, ']')) {
            if ($index > 0) {
                $this->stream->expect(Token::PUNCTUATION, ',');

                // Allow trailing comma
                if ($this->stream->test(Token::PUNCTUATION, ']')) {
                    break;
                }
            }

            $value = $this->parseExpression();
            $elements[] = ['key' => new NumberNode($index, $line), 'value' => $value];
            $index++;
        }

        $this->stream->expect(Token::PUNCTUATION, ']');

        return new ArrayNode($elements, $line);
    }

    private function parseHashExpression(): ArrayNode
    {
        $token = $this->stream->expect(Token::PUNCTUATION, '{');
        $line = $token->line;
        $elements = [];
        $index = 0;

        while (!$this->stream->test(Token::PUNCTUATION, '}')) {
            if ($index > 0) {
                $this->stream->expect(Token::PUNCTUATION, ',');

                if ($this->stream->test(Token::PUNCTUATION, '}')) {
                    break;
                }
            }

            $key = $this->parseExpression();
            $this->stream->expect(Token::PUNCTUATION, ':');
            $value = $this->parseExpression();
            $elements[] = ['key' => $key, 'value' => $value];
            $index++;
        }

        $this->stream->expect(Token::PUNCTUATION, '}');

        return new ArrayNode($elements, $line);
    }

    private function parsePostfixExpression(Node $node): Node
    {
        while (true) {
            $current = $this->stream->current();

            if ($current->test(Token::OPERATOR, '.')) {
                $this->stream->next();
                $attr = $this->stream->expect(Token::NAME);
                $node = new GetAttrNode($node, new StringNode($attr->value, $attr->line), $attr->line);
            } elseif ($current->test(Token::PUNCTUATION, '[')) {
                $this->stream->next();
                $attr = $this->parseExpression();
                $this->stream->expect(Token::PUNCTUATION, ']');
                $node = new GetAttrNode($node, $attr, $current->line);
            } else {
                break;
            }
        }

        return $node;
    }

    private function parseFilterExpression(Node $node): Node
    {
        while ($this->stream->test(Token::OPERATOR, '|')) {
            $this->stream->next();
            $nameToken = $this->stream->expect(Token::NAME);
            $arguments = [];

            // Parse filter arguments: | filter(arg1, arg2)
            if ($this->stream->test(Token::PUNCTUATION, '(')) {
                $this->stream->next();

                $argIndex = 0;
                while (!$this->stream->test(Token::PUNCTUATION, ')')) {
                    if ($argIndex > 0) {
                        $this->stream->expect(Token::PUNCTUATION, ',');
                    }
                    $arguments[] = $this->parseExpression();
                    $argIndex++;
                }

                $this->stream->expect(Token::PUNCTUATION, ')');
            }

            $node = new FilterNode($node, $nameToken->value, $arguments, $nameToken->line);
        }

        return $node;
    }

    private function parseConditionalExpression(Node $node): Node
    {
        // Ternary: expr ? true_expr : false_expr
        if ($this->stream->test(Token::PUNCTUATION, '?')) {
            $this->stream->next();
            $trueExpr = $this->parseExpression();
            $this->stream->expect(Token::PUNCTUATION, ':');
            $falseExpr = $this->parseExpression();

            return new BinaryNode('?:', $node, new BinaryNode(':', $trueExpr, $falseExpr, $node->getLine()), $node->getLine());
        }

        return $node;
    }

    private function parseBinaryExpression(Node $left, int $precedence = 0): Node
    {
        while (true) {
            $current = $this->stream->current();

            if ($current->type !== Token::OPERATOR) {
                break;
            }

            $op = $current->value;

            if (!isset(self::BINARY_OPERATORS[$op])) {
                break;
            }

            $opInfo = self::BINARY_OPERATORS[$op];

            if ($opInfo['precedence'] < $precedence) {
                break;
            }

            $this->stream->next();

            $right = $this->parseUnaryExpression();
            $right = $this->parsePostfixExpression($right);

            // Look ahead for higher precedence operators
            $nextPrec = $opInfo['precedence'];
            if ($opInfo['associativity'] === 'left') {
                $nextPrec++;
            }

            $right = $this->parseBinaryExpression($right, $nextPrec);

            $left = new BinaryNode($op, $left, $right, $current->line);
        }

        return $left;
    }
}
