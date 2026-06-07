<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Perplexity;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class PerplexitySearch implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Search the web for real-time information using the Perplexity Search API.
            Returns a ranked list of sources with titles, URLs, and snippets.
            TXT;
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
                ->description('Maximum number of search results to return (1-20, default: 10)')
                ->nullable()
                ->required(),
            'search_recency_filter' => $schema
                ->string()
                ->description("Restrict results to a recent time window - one of 'hour', 'day', 'week', 'month', or 'year' (default: no filter)")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $query = $request->string('query')->trim();

        if ($query->isEmpty()) {
            return 'The search query is empty. Provide a query to search for.';
        }

        $apiKey = config('services.perplexity.key');

        if (! is_string($apiKey) || blank($apiKey)) {
            return 'The Perplexity tool is not configured. Set services.perplexity.key in your config/services.php file.';
        }

        $maxResults = $request->filled('max_results')
            ? $request->integer('max_results')
            : $this->defaultMaxResults();

        $payload = [
            'query' => (string) $query,
            'max_results' => max(1, min(20, $maxResults)),
        ];

        $recency = $request->filled('search_recency_filter')
            ? $this->validateRecency((string) $request->string('search_recency_filter'))
            : $this->defaultRecency();

        if ($recency !== null) {
            $payload['search_recency_filter'] = $recency;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.perplexity.ai/search', $payload);
        } catch (Throwable $throwable) {
            return sprintf('The Perplexity search request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Perplexity search request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return 'The Perplexity search response was invalid.';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function defaultMaxResults(): int
    {
        $max = config('ai.toolkit.perplexity.search.max_results', 10);

        return is_numeric($max) ? (int) $max : 10;
    }

    private function defaultRecency(): ?string
    {
        $recency = config('ai.toolkit.perplexity.search.recency');

        return is_string($recency) ? $this->validateRecency($recency) : null;
    }

    private function validateRecency(string $recency): ?string
    {
        return in_array($recency, ['hour', 'day', 'week', 'month', 'year'], true) ? $recency : null;
    }
}
