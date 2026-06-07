<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackWebSearch implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Search the web for real-time information using JigsawStack. Returns
            ranked results with an optional AI-generated overview of the answer.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The search query to look up on the web (maximum 400 characters)')
                ->required(),
            'ai_overview' => $schema
                ->boolean()
                ->description('Whether to include an AI-generated overview of the results (default: true)')
                ->nullable()
                ->required(),
            'safe_search' => $schema
                ->string()
                ->description("Safe-search level - 'moderate', 'strict', or 'off' (default: 'moderate')")
                ->nullable()
                ->required(),
            'max_results' => $schema
                ->integer()
                ->description('Maximum number of results to return')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('query')) {
            return 'The search query is empty. Provide a query to search for.';
        }

        $query = trim((string) $request->string('query'));

        $payload = $this->withOptional($request, ['query' => $query], 'ai_overview', 'safe_search', 'max_results');

        return $this->request('post', 'v1/web/search', $payload);
    }
}
