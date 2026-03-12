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

final class VexCompiler
{
    private string $source = '';

    public function compile(ModuleNode $module): string
    {
        $this->source = '';

        $this->writeLine('<?php /* Compiled Vex Template */ ?>');

        $extends = $module->getExtends();
        if ($extends !== null) {
            $this->writeLine(sprintf(
                '<?php $__extends = %s; ?>',
                var_export($extends->getParent(), true),
            ));
        }

        // Compile blocks as closures
        foreach ($module->getBlocks() as $name => $block) {
            $this->compileBlock($block);
        }

        // Compile body (non-block content)
        $this->writeLine('<?php $__body = function() use (&$__context, &$__blocks, &$__engine) { ?>');
        $this->writeLine('<?php extract($__context); ?>');

        foreach ($module->getBody() as $node) {
            $this->compileNode($node);
        }

        $this->writeLine('<?php }; ?>');

        return $this->source;
    }

    private function compileNode(Node $node): void
    {
        if ($node instanceof TextNode) {
            $this->write($node->getData());
        } elseif ($node instanceof PrintNode) {
            $this->compilePrint($node);
        } elseif ($node instanceof BlockNode) {
            $this->compileBlockReference($node);
        } elseif ($node instanceof ForNode) {
            $this->compileFor($node);
        } elseif ($node instanceof IfNode) {
            $this->compileIf($node);
        } elseif ($node instanceof SetNode) {
            $this->compileSet($node);
        } elseif ($node instanceof IncludeNode) {
            $this->compileInclude($node);
        } elseif ($node instanceof ExtendsNode) {
            // Already handled at module level
        } else {
            throw new \RuntimeException(sprintf(
                'Unknown node type "%s" at line %d.',
                get_class($node),
                $node->getLine(),
            ));
        }
    }

    private function compilePrint(PrintNode $node): void
    {
        $expr = $node->getExpression();

        // Check if the expression ends with a raw filter
        if ($this->isRawExpression($expr)) {
            $this->writeLine(sprintf('<?= %s ?>', $this->compileExpression($expr)));
        } else {
            $this->writeLine(sprintf(
                '<?= htmlspecialchars((string)(%s), ENT_QUOTES, \'UTF-8\') ?>',
                $this->compileExpression($expr),
            ));
        }
    }

    private function isRawExpression(Node $expr): bool
    {
        if ($expr instanceof FilterNode) {
            if ($expr->getName() === 'raw') {
                return true;
            }
            // Check if any filter in the chain is 'escape' or 'e' (already escaped)
            if ($expr->getName() === 'escape' || $expr->getName() === 'e') {
                return true;
            }
            // Filters like nl2br, json already handle their own output
            if (in_array($expr->getName(), ['nl2br', 'json', 'url_encode'], true)) {
                return true;
            }
        }

        return false;
    }

    private function compileBlock(BlockNode $block): void
    {
        $name = var_export($block->getName(), true);

        $this->writeLine(sprintf(
            '<?php $__blocks[%s] = function() use (&$__context, &$__blocks, &$__engine) { ?>',
            $name,
        ));
        $this->writeLine('<?php extract($__context); ?>');

        foreach ($block->getBody() as $node) {
            $this->compileNode($node);
        }

        $this->writeLine('<?php }; ?>');
    }

    private function compileBlockReference(BlockNode $block): void
    {
        $name = var_export($block->getName(), true);
        $this->writeLine(sprintf(
            '<?php if (isset($__blocks[%s])) { $__blocks[%s](); } ?>',
            $name,
            $name,
        ));
    }

    private function compileFor(ForNode $node): void
    {
        $sequence = $this->compileExpression($node->getSequence());

        /** @var NameNode $valueTarget */
        $valueTarget = $node->getValueTarget();
        $valueName = '$' . $valueTarget->getName();

        if ($node->hasKey()) {
            /** @var NameNode $keyTarget */
            $keyTarget = $node->getKeyTarget();
            $keyName = '$' . $keyTarget->getName();
        }

        if ($node->hasElse()) {
            $this->writeLine(sprintf('<?php $__seq = %s; ?>', $sequence));
            $this->writeLine('<?php if (!empty($__seq)): ?>');

            if ($node->hasKey()) {
                $this->writeLine(sprintf(
                    '<?php foreach ($__seq as %s => %s): ?>',
                    $keyName,
                    $valueName,
                ));
            } else {
                $this->writeLine(sprintf(
                    '<?php foreach ($__seq as %s): ?>',
                    $valueName,
                ));
            }

            // Update context for inner scope access
            $this->writeLine(sprintf('<?php $__context[%s] = %s; ?>', var_export($valueTarget->getName(), true), $valueName));
            if ($node->hasKey()) {
                /** @var NameNode $keyTarget */
                $keyTarget = $node->getKeyTarget();
                $this->writeLine(sprintf('<?php $__context[%s] = %s; ?>', var_export($keyTarget->getName(), true), $keyName));
            }

            foreach ($node->getBody() as $child) {
                $this->compileNode($child);
            }

            $this->writeLine('<?php endforeach; ?>');
            $this->writeLine('<?php else: ?>');

            foreach ($node->getElseBody() as $child) {
                $this->compileNode($child);
            }

            $this->writeLine('<?php endif; ?>');
        } else {
            if ($node->hasKey()) {
                $this->writeLine(sprintf(
                    '<?php foreach ((%s) as %s => %s): ?>',
                    $sequence,
                    $keyName,
                    $valueName,
                ));
            } else {
                $this->writeLine(sprintf(
                    '<?php foreach ((%s) as %s): ?>',
                    $sequence,
                    $valueName,
                ));
            }

            $this->writeLine(sprintf('<?php $__context[%s] = %s; ?>', var_export($valueTarget->getName(), true), $valueName));
            if ($node->hasKey()) {
                /** @var NameNode $keyTarget */
                $keyTarget = $node->getKeyTarget();
                $this->writeLine(sprintf('<?php $__context[%s] = %s; ?>', var_export($keyTarget->getName(), true), $keyName));
            }

            foreach ($node->getBody() as $child) {
                $this->compileNode($child);
            }

            $this->writeLine('<?php endforeach; ?>');
        }
    }

    private function compileIf(IfNode $node): void
    {
        $branches = $node->getBranches();
        $first = true;

        foreach ($branches as $branch) {
            $condition = $this->compileExpression($branch['condition']);

            if ($first) {
                $this->writeLine(sprintf('<?php if (%s): ?>', $condition));
                $first = false;
            } else {
                $this->writeLine(sprintf('<?php elseif (%s): ?>', $condition));
            }

            foreach ($branch['body'] as $child) {
                $this->compileNode($child);
            }
        }

        if ($node->hasElse()) {
            $this->writeLine('<?php else: ?>');
            foreach ($node->getElseBody() as $child) {
                $this->compileNode($child);
            }
        }

        $this->writeLine('<?php endif; ?>');
    }

    private function compileSet(SetNode $node): void
    {
        $name = var_export($node->getName(), true);
        $value = $this->compileExpression($node->getValue());

        $this->writeLine(sprintf('<?php $%s = %s; $__context[%s] = $%s; ?>', $node->getName(), $value, $name, $node->getName()));
    }

    private function compileInclude(IncludeNode $node): void
    {
        $this->writeLine(sprintf(
            '<?= $__engine->render(%s, $__context) ?>',
            var_export($node->getTemplate(), true),
        ));
    }

    // -----------------------------------------------------------------------
    //  Expression compilation
    // -----------------------------------------------------------------------

    private function compileExpression(Node $expr): string
    {
        if ($expr instanceof StringNode) {
            return var_export($expr->getValue(), true);
        }

        if ($expr instanceof NumberNode) {
            return var_export($expr->getValue(), true);
        }

        if ($expr instanceof BooleanNode) {
            return $expr->getValue() ? 'true' : 'false';
        }

        if ($expr instanceof NullNode) {
            return 'null';
        }

        if ($expr instanceof NameNode) {
            return $this->compileNameExpression($expr);
        }

        if ($expr instanceof BinaryNode) {
            return $this->compileBinaryExpression($expr);
        }

        if ($expr instanceof UnaryNode) {
            return $this->compileUnaryExpression($expr);
        }

        if ($expr instanceof FilterNode) {
            return $this->compileFilter($expr);
        }

        if ($expr instanceof GetAttrNode) {
            return $this->compileGetAttr($expr);
        }

        if ($expr instanceof ArrayNode) {
            return $this->compileArray($expr);
        }

        throw new \RuntimeException(sprintf(
            'Cannot compile expression of type "%s".',
            get_class($expr),
        ));
    }

    private function compileNameExpression(NameNode $node): string
    {
        $name = $node->getName();

        // Special variable: loop is handled by PHP foreach
        return '$' . $name;
    }

    private function compileBinaryExpression(BinaryNode $node): string
    {
        $left = $this->compileExpression($node->getLeft());
        $right = $this->compileExpression($node->getRight());
        $op = $node->getOperator();

        // Handle ternary that was parsed as binary
        if ($op === '?:') {
            /** @var BinaryNode $rightNode */
            $rightNode = $node->getRight();
            $trueExpr = $this->compileExpression($rightNode->getLeft());
            $falseExpr = $this->compileExpression($rightNode->getRight());
            return sprintf('((%s) ? (%s) : (%s))', $left, $trueExpr, $falseExpr);
        }

        return match ($op) {
            'and', '&&' => sprintf('((%s) && (%s))', $left, $right),
            'or', '||' => sprintf('((%s) || (%s))', $left, $right),
            'in' => sprintf('(is_array(%2$s) ? in_array(%1$s, %2$s, false) : str_contains((string)%2$s, (string)%1$s))', $left, $right),
            'is' => sprintf('(%s === %s)', $left, $right),
            '~' => sprintf('((%s) . (%s))', $left, $right),
            default => sprintf('((%s) %s (%s))', $left, $op, $right),
        };
    }

    private function compileUnaryExpression(UnaryNode $node): string
    {
        $operand = $this->compileExpression($node->getOperand());

        return match ($node->getOperator()) {
            'not' => sprintf('(!(%s))', $operand),
            '-' => sprintf('(-(%s))', $operand),
            '+' => sprintf('(+(%s))', $operand),
            default => throw new \RuntimeException(sprintf(
                'Unknown unary operator "%s".',
                $node->getOperator(),
            )),
        };
    }

    private function compileGetAttr(GetAttrNode $node): string
    {
        $object = $this->compileExpression($node->getNode());
        $attr = $node->getAttribute();

        if ($attr instanceof StringNode) {
            $key = var_export($attr->getValue(), true);
            // Support both array access and object property access
            return sprintf(
                '(is_array(%1$s) ? (%1$s[%2$s] ?? null) : (is_object(%1$s) ? (%1$s->%3$s ?? null) : null))',
                $object,
                $key,
                $attr->getValue(),
            );
        }

        // Dynamic attribute access (bracket notation)
        $key = $this->compileExpression($attr);
        return sprintf('(%1$s[%2$s] ?? null)', $object, $key);
    }

    private function compileArray(ArrayNode $node): string
    {
        $elements = $node->getElements();

        if (count($elements) === 0) {
            return '[]';
        }

        $parts = [];
        foreach ($elements as $element) {
            $key = $this->compileExpression($element['key']);
            $value = $this->compileExpression($element['value']);
            $parts[] = sprintf('%s => %s', $key, $value);
        }

        return '[' . implode(', ', $parts) . ']';
    }

    private function compileFilter(FilterNode $filter): string
    {
        $value = $this->compileExpression($filter->getNode());
        $name = $filter->getName();
        $args = $filter->getArguments();

        return match ($name) {
            'escape', 'e' => sprintf('htmlspecialchars((string)(%s), ENT_QUOTES, \'UTF-8\')', $value),
            'raw' => sprintf('(%s)', $value),
            'upper' => sprintf('mb_strtoupper((string)(%s))', $value),
            'lower' => sprintf('mb_strtolower((string)(%s))', $value),
            'title' => sprintf('mb_convert_case((string)(%s), MB_CASE_TITLE)', $value),
            'capitalize' => sprintf('ucfirst(mb_strtolower((string)(%s)))', $value),
            'trim' => sprintf('trim((string)(%s))', $value),
            'striptags' => sprintf('strip_tags((string)(%s))', $value),
            'nl2br' => sprintf('nl2br(htmlspecialchars((string)(%s), ENT_QUOTES, \'UTF-8\'))', $value),
            'url_encode' => sprintf('urlencode((string)(%s))', $value),
            'json' => sprintf('json_encode(%s, JSON_THROW_ON_ERROR)', $value),
            'abs' => sprintf('abs(%s)', $value),
            'round' => $this->compileRoundFilter($value, $args),
            'length' => sprintf('(is_array(%1$s) || %1$s instanceof \Countable ? count(%1$s) : mb_strlen((string)(%1$s)))', $value),
            'default' => $this->compileDefaultFilter($value, $args),
            'date' => $this->compileDateFilter($value, $args),
            'number_format' => $this->compileNumberFormatFilter($value, $args),
            'join' => $this->compileJoinFilter($value, $args),
            'split' => $this->compileSplitFilter($value, $args),
            'reverse' => sprintf('(is_array(%1$s) ? array_reverse(%1$s) : strrev((string)(%1$s)))', $value),
            'sort' => sprintf('(function($a) { if (is_array($a)) { sort($a); return $a; } return $a; })(%s)', $value),
            'keys' => sprintf('array_keys((array)(%s))', $value),
            'first' => sprintf('(is_array(%1$s) ? reset(%1$s) : mb_substr((string)(%1$s), 0, 1))', $value),
            'last' => sprintf('(is_array(%1$s) ? end(%1$s) : mb_substr((string)(%1$s), -1))', $value),
            'slice' => $this->compileSliceFilter($value, $args),
            'merge' => $this->compileMergeFilter($value, $args),
            'replace' => $this->compileReplaceFilter($value, $args),
            'batch' => $this->compileBatchFilter($value, $args),
            default => throw new \RuntimeException(sprintf('Unknown filter "%s".', $name)),
        };
    }

    private function compileDefaultFilter(string $value, array $args): string
    {
        $default = count($args) > 0 ? $this->compileExpression($args[0]) : "''";
        return sprintf('((%1$s) ?? (%2$s))', $value, $default);
    }

    private function compileDateFilter(string $value, array $args): string
    {
        $format = count($args) > 0 ? $this->compileExpression($args[0]) : "'Y-m-d H:i:s'";

        return sprintf(
            '(function($v, $f) { '
            . 'if ($v instanceof \DateTimeInterface) { return $v->format($f); } '
            . 'if (is_numeric($v)) { return date($f, (int)$v); } '
            . 'return date($f, strtotime((string)$v)); '
            . '})(%s, %s)',
            $value,
            $format,
        );
    }

    private function compileNumberFormatFilter(string $value, array $args): string
    {
        $decimals = count($args) > 0 ? $this->compileExpression($args[0]) : '0';
        $decPoint = count($args) > 1 ? $this->compileExpression($args[1]) : "'.'";
        $thousandsSep = count($args) > 2 ? $this->compileExpression($args[2]) : "','";

        return sprintf(
            'number_format((float)(%s), %s, %s, %s)',
            $value,
            $decimals,
            $decPoint,
            $thousandsSep,
        );
    }

    private function compileJoinFilter(string $value, array $args): string
    {
        $separator = count($args) > 0 ? $this->compileExpression($args[0]) : "''";
        return sprintf('implode(%s, (array)(%s))', $separator, $value);
    }

    private function compileSplitFilter(string $value, array $args): string
    {
        $delimiter = count($args) > 0 ? $this->compileExpression($args[0]) : "''";
        return sprintf('explode(%s, (string)(%s))', $delimiter, $value);
    }

    private function compileSliceFilter(string $value, array $args): string
    {
        $start = count($args) > 0 ? $this->compileExpression($args[0]) : '0';
        $length = count($args) > 1 ? $this->compileExpression($args[1]) : 'null';

        return sprintf(
            '(is_array(%1$s) ? array_slice(%1$s, %2$s, %3$s) : mb_substr((string)(%1$s), %2$s, %3$s))',
            $value,
            $start,
            $length,
        );
    }

    private function compileMergeFilter(string $value, array $args): string
    {
        if (count($args) === 0) {
            return $value;
        }
        $merge = $this->compileExpression($args[0]);
        return sprintf('array_merge((array)(%s), (array)(%s))', $value, $merge);
    }

    private function compileReplaceFilter(string $value, array $args): string
    {
        if (count($args) < 2) {
            throw new \RuntimeException('Filter "replace" requires at least 2 arguments (search, replace).');
        }
        $search = $this->compileExpression($args[0]);
        $replace = $this->compileExpression($args[1]);
        return sprintf('str_replace(%s, %s, (string)(%s))', $search, $replace, $value);
    }

    private function compileBatchFilter(string $value, array $args): string
    {
        $size = count($args) > 0 ? $this->compileExpression($args[0]) : '1';
        return sprintf('array_chunk((array)(%s), max(1, (int)(%s)))', $value, $size);
    }

    private function compileRoundFilter(string $value, array $args): string
    {
        $precision = count($args) > 0 ? $this->compileExpression($args[0]) : '0';
        return sprintf('round((float)(%s), (int)(%s))', $value, $precision);
    }

    // -----------------------------------------------------------------------
    //  Output helpers
    // -----------------------------------------------------------------------

    private function write(string $string): void
    {
        $this->source .= $string;
    }

    private function writeLine(string $string): void
    {
        $this->source .= $string . "\n";
    }
}
