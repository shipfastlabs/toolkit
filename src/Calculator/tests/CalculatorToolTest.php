<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Calculator\CalculatorTool;

function evaluate(string $expression): string
{
    return (new CalculatorTool)->handle(new Request(['expression' => $expression]));
}

it('has a description', function (): void {
    expect((new CalculatorTool)->description())->toContain('mathematical expression');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new CalculatorTool))->toBeTrue();
});

it('exposes a required expression schema', function (): void {
    $schema = (new CalculatorTool)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('expression')
        ->and($schema['expression']->toArray())
        ->toMatchArray(['type' => 'string'])
        ->toHaveKey('description');
});

it('evaluates expressions', function (string $expression, string $expected): void {
    expect(evaluate($expression))->toBe($expected);
})->with([
    'addition' => ['2 + 3', '5'],
    'precedence' => ['2 + 3 * 4', '14'],
    'parentheses' => ['(2 + 3) * 4', '20'],
    'exponent' => ['4 ^ 2', '16'],
    'right associative exponent' => ['2 ^ 3 ^ 2', '512'],
    'unary minus' => ['-5 + 2', '-3'],
    'unary plus' => ['+5 + 2', '7'],
    'nested unary' => ['--5', '5'],
    'decimals' => ['0.1 + 0.2', '0.3'],
    'modulo' => ['10 % 3', '1'],
    'division' => ['10 / 4', '2.5'],
    'whitespace' => ['  7  *  6 ', '42'],
]);

it('returns a friendly message for invalid input', function (string $expression, string $message): void {
    expect(evaluate($expression))->toContain($message);
})->with([
    'division by zero' => ['1 / 0', 'Division by zero'],
    'modulo by zero' => ['10 % 0', 'Modulo by zero'],
    'trailing operator' => ['2 +', 'Unable to evaluate'],
    'two numbers' => ['2 3', 'Unexpected token'],
    'unexpected character' => ['abc', 'Unexpected character'],
    'unclosed parenthesis' => ['(2 + 3', 'Expected a closing parenthesis'],
    'empty parentheses' => ['()', 'Unexpected token'],
    'malformed number' => ['1.2.3', 'Invalid number'],
    'empty expression' => ['   ', 'The expression is empty'],
]);

it('reports a non-finite result', function (): void {
    expect(evaluate('9 ^ 9 ^ 9'))->toContain('does not evaluate to a finite number');
});
