<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\Concerns\InteractsWithFirecrawl;

#[Strict]
class FirecrawlSearch implements Tool
{
    use InteractsWithFirecrawl;

    /**
     * @var list<string>
     */
    private const array SOURCES = ['web', 'news', 'images'];

    public function description(): string
    {
        return <<<'TXT'
            Search the web and return ranked results. Use this to find pages when you do not
            already have a URL. Optionally pull results from the news or images sources, and
            optionally scrape each result into markdown so you get the page content, not just
            the link.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The search query to look up on the web.')
                ->required(),
            'limit' => $schema
                ->integer()
                ->description('Maximum number of results to return (1-20, default: 5).')
                ->nullable()
                ->required(),
            'sources' => $schema
                ->string()
                ->description("Comma-separated result sources, any of 'web', 'news', 'images' (default: 'web').")
                ->nullable()
                ->required(),
            'scrape_content' => $schema
                ->boolean()
                ->description('Scrape each result and include its markdown content, not just the link (default: false).')
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

        $apiKey = $this->firecrawlApiKey();

        if ($apiKey === null) {
            return $this->firecrawlNotConfiguredMessage();
        }

        $payload = [
            'query' => (string) $query,
            'limit' => $this->firecrawlResolveLimit($request, 'ai.toolkit.firecrawl.search.limit', 5, 1, 20),
            'sources' => $this->resolveSources($request),
        ];

        if ($this->resolveScrapeContent($request)) {
            $payload['scrapeOptions'] = [
                'formats' => ['markdown'],
                'onlyMainContent' => true,
            ];
        }

        return $this->firecrawlPost($apiKey, 120, '/v2/search', $payload, 'search');
    }

    /**
     * @return list<string>
     */
    private function resolveSources(Request $request): array
    {
        $raw = $request->filled('sources')
            ? (string) $request->string('sources')
            : $this->firecrawlConfigString('ai.toolkit.firecrawl.search.sources', 'web');

        return $this->firecrawlCsvAllowList($raw, self::SOURCES, 'web');
    }

    private function resolveScrapeContent(Request $request): bool
    {
        if ($request->has('scrape_content') && $request['scrape_content'] !== null) {
            return $request->boolean('scrape_content');
        }

        return false;
    }
}
