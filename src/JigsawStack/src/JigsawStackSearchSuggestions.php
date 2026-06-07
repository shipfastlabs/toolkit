<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackSearchSuggestions implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Get autocomplete search suggestions for a partial query using
            JigsawStack. Useful for building type-ahead search experiences.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The partial search query to get suggestions for (maximum 200 characters)')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('query')) {
            return 'The query is empty. Provide a query to get suggestions for.';
        }

        $query = trim((string) $request->string('query'));

        return $this->request('get', 'v1/web/search/suggest', ['query' => $query]);
    }
}
