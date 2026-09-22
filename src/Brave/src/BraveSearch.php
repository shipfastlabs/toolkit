<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Brave;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class BraveSearch implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Search the web for real-time information using the Brave Search API.
            Returns ranked web results with titles, URLs, and snippets.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The search query to look up on the web')
                ->required(),
            'count' => $schema
                ->integer()
                ->description('Maximum number of search results to return (1-20, default: 20)')
                ->nullable()
                ->required(),
            'offset' => $schema
                ->integer()
                ->description('Zero-based page offset for pagination (0-9, default: 0)')
                ->nullable()
                ->required(),
            'freshness' => $schema
                ->string()
                ->description("Restrict results by freshness - 'pd' (24h), 'pw' (7d), 'pm' (31d), 'py' (year), or a 'YYYY-MM-DDtoYYYY-MM-DD' range")
                ->nullable()
                ->required(),
            'safesearch' => $schema
                ->string()
                ->description("Adult content filter - 'off', 'moderate', or 'strict' (default: 'moderate')")
                ->nullable()
                ->required(),
            'country' => $schema
                ->string()
                ->description('Two-character country code to prefer results from (e.g., "US", "DE")')
                ->nullable()
                ->required(),
            'search_lang' => $schema
                ->string()
                ->description('ISO 639-1 language code to prefer for result content (e.g., "en", "de")')
                ->nullable()
                ->required(),
            'extra_snippets' => $schema
                ->boolean()
                ->description('Whether to include up to five additional excerpts per result (default: false)')
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

        $apiKey = config('services.brave.key');

        if (! is_string($apiKey) || blank($apiKey)) {
            return 'The Brave tool is not configured. Set services.brave.key in your config/services.php file.';
        }

        $count = $request->filled('count')
            ? $request->integer('count')
            : $this->defaultCount();

        $offset = $request->filled('offset')
            ? $request->integer('offset')
            : $this->defaultOffset();

        $queryParams = [
            'q' => (string) $query,
            'count' => max(1, min(20, $count)),
            'offset' => max(0, min(9, $offset)),
            'safesearch' => $request->filled('safesearch')
                ? $this->validateSafesearch((string) $request->string('safesearch'))
                : $this->defaultSafesearch(),
        ];

        $freshness = $request->filled('freshness')
            ? $this->validateFreshness((string) $request->string('freshness'))
            : $this->defaultFreshness();

        if ($freshness !== null) {
            $queryParams['freshness'] = $freshness;
        }

        $country = $request->filled('country')
            ? trim((string) $request->string('country'))
            : $this->defaultCountry();

        if ($country !== null && $country !== '') {
            $queryParams['country'] = $country;
        }

        $searchLang = $request->filled('search_lang')
            ? trim((string) $request->string('search_lang'))
            : $this->defaultSearchLang();

        if ($searchLang !== null && $searchLang !== '') {
            $queryParams['search_lang'] = $searchLang;
        }

        $extraSnippets = $request->filled('extra_snippets')
            ? $request->boolean('extra_snippets')
            : $this->defaultExtraSnippets();

        if ($extraSnippets) {
            $queryParams['extra_snippets'] = true;
        }

        try {
            $response = Http::withHeaders([
                'X-Subscription-Token' => $apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout(30)
                ->get('https://api.search.brave.com/res/v1/web/search', $queryParams);
        } catch (Throwable $throwable) {
            return sprintf('The Brave search request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Brave search request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return 'The Brave search response was invalid.';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function defaultCount(): int
    {
        $count = config('ai.toolkit.brave.search.count', 20);

        return is_numeric($count) ? (int) $count : 20;
    }

    private function defaultOffset(): int
    {
        $offset = config('ai.toolkit.brave.search.offset', 0);

        return is_numeric($offset) ? (int) $offset : 0;
    }

    private function defaultSafesearch(): string
    {
        $safesearch = config('ai.toolkit.brave.search.safesearch', 'moderate');

        return $this->validateSafesearch(is_string($safesearch) ? $safesearch : 'moderate');
    }

    private function validateSafesearch(string $safesearch): string
    {
        return in_array($safesearch, ['off', 'moderate', 'strict'], true) ? $safesearch : 'moderate';
    }

    private function defaultFreshness(): ?string
    {
        $freshness = config('ai.toolkit.brave.search.freshness');

        return is_string($freshness) ? $this->validateFreshness($freshness) : null;
    }

    private function validateFreshness(string $freshness): ?string
    {
        if (in_array($freshness, ['pd', 'pw', 'pm', 'py'], true)) {
            return $freshness;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}to\d{4}-\d{2}-\d{2}$/', $freshness) === 1) {
            return $freshness;
        }

        return null;
    }

    private function defaultCountry(): ?string
    {
        $country = config('ai.toolkit.brave.search.country');

        return is_string($country) && $country !== '' ? $country : null;
    }

    private function defaultSearchLang(): ?string
    {
        $searchLang = config('ai.toolkit.brave.search.search_lang');

        return is_string($searchLang) && $searchLang !== '' ? $searchLang : null;
    }

    private function defaultExtraSnippets(): bool
    {
        return (bool) config('ai.toolkit.brave.search.extra_snippets', false);
    }
}
