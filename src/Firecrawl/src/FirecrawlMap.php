<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\Concerns\InteractsWithFirecrawl;

#[Strict]
class FirecrawlMap implements Tool
{
    use InteractsWithFirecrawl;

    public function description(): string
    {
        return <<<'TXT'
            Map a website and return its list of URLs. Use this to discover every page on a
            site quickly before deciding which ones to scrape, or to understand a site's
            structure. Optionally filter the URLs by a search term.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The base URL of the website to map.')
                ->required(),
            'search' => $schema
                ->string()
                ->description('Only return URLs that match this search term (default: no filter).')
                ->nullable()
                ->required(),
            'limit' => $schema
                ->integer()
                ->description('Maximum number of URLs to return (1-5000, default: 100).')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $url = $request->string('url')->trim();

        if ($url->isEmpty()) {
            return 'The URL is empty. Provide a URL to map.';
        }

        $apiKey = $this->firecrawlApiKey();

        if ($apiKey === null) {
            return $this->firecrawlNotConfiguredMessage();
        }

        $payload = [
            'url' => (string) $url,
            'limit' => $this->firecrawlResolveLimit($request, 'ai.toolkit.firecrawl.map.limit', 100, 1, 5000),
        ];

        $search = $request->string('search')->trim();

        if ($search->isNotEmpty()) {
            $payload['search'] = (string) $search;
        }

        return $this->firecrawlPost($apiKey, 60, '/v2/map', $payload, 'map');
    }
}
