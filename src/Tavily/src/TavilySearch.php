<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Tavily;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class TavilySearch implements Tool
{
    public function description(): string
    {
        return "Search the web for real-time information using Tavily's AI-optimized "
            .'search engine. Returns relevant sources, snippets, and optional '
            .'AI-generated answers.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The search query to look up on the web')
                ->required(),
            'max_results' => $schema
                ->integer()
                ->description('Maximum number of search results to return (1-10, default: 5)')
                ->nullable()
                ->required(),
            'search_depth' => $schema
                ->string()
                ->description("The depth of the search - 'basic' for quick results, 'advanced' for comprehensive search (default: 'basic')")
                ->nullable()
                ->required(),
            'include_answer' => $schema
                ->boolean()
                ->description('Whether to include an AI-generated answer summarizing the results (default: false)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $query = trim((string) $request->string('query'));

        if ($query === '') {
            return 'The search query is empty. Provide a query to search for.';
        }

        $apiKey = config('services.tavily.key');

        if (empty($apiKey)) {
            return 'The Tavily tool is not configured. Set services.tavily.key in your config/services.php file.';
        }

        $maxResults = $request->has('max_results') && $request['max_results'] !== null
            ? $request->integer('max_results')
            : $this->defaultMaxResults();

        $searchDepth = $request->has('search_depth') && $request['search_depth'] !== null
            ? $this->validateSearchDepth((string) $request->string('search_depth'))
            : $this->defaultSearchDepth();

        $includeAnswer = $request->has('include_answer') && $request['include_answer'] !== null
            ? $request->boolean('include_answer')
            : $this->defaultIncludeAnswer();

        try {
            $response = Http::timeout(30)
                ->post('https://api.tavily.com/search', [
                    'api_key' => $apiKey,
                    'query' => $query,
                    'max_results' => max(1, min(10, $maxResults)),
                    'search_depth' => $searchDepth,
                    'include_answer' => $includeAnswer,
                ]);
        } catch (Throwable $throwable) {
            return sprintf('The Tavily search request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Tavily search request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return 'The Tavily search response was invalid.';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function defaultMaxResults(): int
    {
        $max = config('ai.toolkit.tavily.search.max_results', 5);

        return is_numeric($max) ? (int) $max : 5;
    }

    private function defaultSearchDepth(): string
    {
        $depth = config('ai.toolkit.tavily.search.search_depth', 'basic');

        return $this->validateSearchDepth(is_string($depth) ? $depth : 'basic');
    }

    private function validateSearchDepth(string $depth): string
    {
        return in_array($depth, ['basic', 'advanced'], true) ? $depth : 'basic';
    }

    private function defaultIncludeAnswer(): bool
    {
        return (bool) config('ai.toolkit.tavily.search.include_answer', false);
    }
}
