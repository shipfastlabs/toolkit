<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Exa;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\Concerns\InteractsWithExa;

#[Strict]
class ExaSearch implements Tool
{
    use InteractsWithExa;

    public function description(): string
    {
        return <<<'DESCRIPTION'
            Search the web for code docs, current information, news, articles, and content. Use this when you need up-to-date information or facts from the internet. Performs real-time web searches and can scrape content from specific URLs.
            DESCRIPTION;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description("The web search query - be specific and clear about what you're looking for")
                ->required(),
            'type' => $schema
                ->string()
                ->description("Search type - 'auto' intelligently combines keyword and neural, 'keyword' is fast keyword search, 'neural' is deep semantic search, 'fast' is streamlined neural and keyword, 'deep' is comprehensive deep search with enhanced analysis (default: 'auto')")
                ->nullable()
                ->required(),
            'num_results' => $schema
                ->integer()
                ->description('Number of results to return (1-25, default: 10)')
                ->nullable()
                ->required(),
            'category' => $schema
                ->string()
                ->description("Category to focus the search on - one of 'company', 'research paper', 'news', 'pdf', 'github', 'personal site', 'linkedin profile', 'financial report'")
                ->nullable()
                ->required(),
            'include_domains' => $schema
                ->string()
                ->description('Comma-separated list of domains to include (e.g., "arxiv.org,github.com")')
                ->nullable()
                ->required(),
            'exclude_domains' => $schema
                ->string()
                ->description('Comma-separated list of domains to exclude')
                ->nullable()
                ->required(),
            'include_text' => $schema
                ->boolean()
                ->description('Whether to retrieve the page text content for each result (default: true)')
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

        $apiKey = $this->exaApiKey();

        if ($apiKey === null) {
            return $this->exaNotConfiguredMessage();
        }

        $payload = [
            'query' => $query,
            'type' => $request->has('type') && $request['type'] !== null
                ? $this->validateType((string) $request->string('type'))
                : $this->defaultType(),
            'numResults' => $this->exaNumResults($request, 'ai.toolkit.exa.search.num_results'),
        ];

        if ($request->has('category') && $request['category'] !== null) {
            $category = $this->validateCategory((string) $request->string('category'));

            if ($category !== null) {
                $payload['category'] = $category;
            }
        }

        $includeDomains = $this->parseDomains($request, 'include_domains');

        if ($includeDomains !== []) {
            $payload['includeDomains'] = $includeDomains;
        }

        $excludeDomains = $this->parseDomains($request, 'exclude_domains');

        if ($excludeDomains !== []) {
            $payload['excludeDomains'] = $excludeDomains;
        }

        if ($this->exaIncludeText($request, 'ai.toolkit.exa.search.include_text', true)) {
            $payload['contents'] = ['text' => true];
        }

        return $this->exaRequest($apiKey, 30, '/search', $payload, 'search');
    }

    private function defaultType(): string
    {
        $type = config('ai.toolkit.exa.search.type', 'auto');

        return $this->validateType(is_string($type) ? $type : 'auto');
    }

    private function validateType(string $type): string
    {
        return in_array($type, ['auto', 'keyword', 'neural', 'fast', 'deep'], true) ? $type : 'auto';
    }

    private function validateCategory(string $category): ?string
    {
        $allowed = [
            'company', 'research paper', 'news', 'pdf', 'github',
            'personal site', 'linkedin profile', 'financial report',
        ];

        return in_array($category, $allowed, true) ? $category : null;
    }
}
