<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JsonException;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackPrediction implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Forecast future values from a time-series dataset using JigsawStack.
            Provide at least five historical data points and the number of future
            steps to predict.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'dataset' => $schema
                ->string()
                ->description('A JSON array of historical data points, each an object with a "date" (YYYY-MM-DD) and a "value" (number). Provide 5 to 1000 points.')
                ->required(),
            'steps' => $schema
                ->integer()
                ->description('The number of future predictions to generate (1-500, default: 5)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('dataset')) {
            return 'The dataset is empty. Provide a JSON array of {"date","value"} points.';
        }

        try {
            $dataset = json_decode(trim((string) $request->string('dataset')), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return 'The dataset must be valid JSON. Provide a JSON array of {"date","value"} points.';
        }

        if (! is_array($dataset)) {
            return 'The dataset must be a JSON array of {"date","value"} points.';
        }

        $payload = $this->withOptional($request, ['dataset' => $dataset], 'steps');

        return $this->request('post', 'v1/ai/prediction', $payload);
    }
}
