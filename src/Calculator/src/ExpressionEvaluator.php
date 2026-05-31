<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Calculator;

use InvalidArgumentException;
use LogicException;

class ExpressionEvaluator
{
    /** @var list<array{type: string, value: string}> */
    private array $tokens = [];

    private int $position = 0;

    /**
     * @throws InvalidArgumentException when the expression is malformed.
     */
    public function evaluate(string $expression): float
    {
        $this->tokens = $this->tokenize($expression);
        $this->position = 0;

        if ($this->tokens === []) {
            throw new InvalidArgumentException('The expression is empty.');
        }

        $result = $this->parseExpression();

        if ($this->position < count($this->tokens)) {
            throw new InvalidArgumentException('Unexpected token "'.$this->tokens[$this->position]['value'].'".');
        }

        return $result;
    }

    /**
     * @return list<array{type: string, value: string}>
     */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                continue;
            }

            if (ctype_digit($char) || $char === '.') {
                $number = '';

                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $number .= $expression[$i];
                    $i++;
                }

                $i--;

                if (! is_numeric($number)) {
                    throw new InvalidArgumentException('Invalid number "'.$number.'".');
                }

                $tokens[] = ['type' => 'number', 'value' => $number];

                continue;
            }

            if (in_array($char, ['+', '-', '*', '/', '%', '^', '(', ')'], true)) {
                $tokens[] = ['type' => 'operator', 'value' => $char];

                continue;
            }

            throw new InvalidArgumentException('Unexpected character "'.$char.'".');
        }

        return $tokens;
    }

    /**
     * @phpstan-impure advances the token cursor ($this->position).
     */
    private function parseExpression(): float
    {
        $value = $this->parseTerm();

        while ($this->matches('+') || $this->matches('-')) {
            $operator = $this->consume()['value'];
            $right = $this->parseTerm();
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    private function parseTerm(): float
    {
        $value = $this->parseFactor();

        while ($this->matches('*') || $this->matches('/') || $this->matches('%')) {
            $operator = $this->consume()['value'];
            $right = $this->parseFactor();

            $value = match ($operator) {
                '*' => $value * $right,
                '/' => $this->divide($value, $right),
                default => $this->modulo($value, $right),
            };
        }

        return $value;
    }

    private function parseFactor(): float
    {
        $value = $this->parseUnary();

        if ($this->matches('^')) {
            $this->consume();

            return $value ** $this->parseFactor();
        }

        return $value;
    }

    private function parseUnary(): float
    {
        if ($this->matches('-')) {
            $this->consume();

            return -$this->parseUnary();
        }

        if ($this->matches('+')) {
            $this->consume();

            return $this->parseUnary();
        }

        return $this->parsePrimary();
    }

    private function parsePrimary(): float
    {
        $token = $this->peek();

        if ($token === null) {
            throw new InvalidArgumentException('Unexpected end of expression.');
        }

        if ($token['type'] === 'number') {
            $this->consume();

            return (float) $token['value'];
        }

        if ($token['value'] === '(') {
            $this->consume();
            $value = $this->parseExpression();

            if (! $this->matches(')')) {
                throw new InvalidArgumentException('Expected a closing parenthesis.');
            }

            $this->consume();

            return $value;
        }

        throw new InvalidArgumentException('Unexpected token "'.$token['value'].'".');
    }

    private function divide(float $left, float $right): float
    {
        if ($right === 0.0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        return $left / $right;
    }

    private function modulo(float $left, float $right): float
    {
        if ($right === 0.0) {
            throw new InvalidArgumentException('Modulo by zero.');
        }

        return fmod($left, $right);
    }

    private function matches(string $operator): bool
    {
        $token = $this->peek();

        return $token !== null && $token['type'] === 'operator' && $token['value'] === $operator;
    }

    /**
     * @return array{type: string, value: string}|null
     */
    private function peek(): ?array
    {
        return $this->tokens[$this->position] ?? null;
    }

    /**
     * @return array{type: string, value: string}
     */
    private function consume(): array
    {
        $token = $this->peek() ?? throw new LogicException('consume() called with no remaining tokens.');

        $this->position++;

        return $token;
    }
}
