<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Calculator;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class CalculatorTool implements Tool
{
    public function __construct(
        private readonly ExpressionEvaluator $evaluator = new ExpressionEvaluator,
    ) {}

    public function description(): string
    {
        return <<<'TXT'
            Evaluate a mathematical expression and return the numeric result.
            Use this whenever you need to perform arithmetic instead of computing it yourself,
            as it is always accurate. Supports +, -, *, /, %, ^ (exponent), parentheses and
            decimal numbers, e.g. "(2 + 3) * 4 ^ 2".
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'expression' => $schema
                ->string()
                ->description('The mathematical expression to evaluate, e.g. "3 * (4 + 1)".')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $expression = (string) $request->string('expression');

        try {
            $result = $this->evaluator->evaluate($expression);
        } catch (Throwable $throwable) {
            return sprintf('Unable to evaluate the expression [%s]: %s', $expression, $throwable->getMessage());
        }

        if (! is_finite($result)) {
            return sprintf('The expression [%s] does not evaluate to a finite number.', $expression);
        }

        return $this->format($result);
    }

    private function format(float $result): string
    {
        if (floor($result) === $result && abs($result) < 1e15) {
            return (string) (int) $result;
        }

        return (string) $result;
    }
}
