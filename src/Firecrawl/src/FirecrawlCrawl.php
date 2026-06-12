<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\Concerns\InteractsWithFirecrawl;

#[Strict]
class FirecrawlCrawl implements Tool
{
    use InteractsWithFirecrawl;

    public function description(): string
    {
        return <<<'TXT'
            Start crawling a website to extract content from many of its pages. Crawling runs
            asynchronously: this tool returns a crawl id immediately, it does not wait for the
            crawl to finish. Pass that id to the Firecrawl crawl status tool to poll progress
            and collect the results. Use this for whole-site or whole-section extraction; to
            read a single page use the scrape tool instead.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL to start crawling from.')
                ->required(),
            'limit' => $schema
                ->integer()
                ->description('Maximum number of pages to crawl (1-1000, default: 10).')
                ->nullable()
                ->required(),
            'prompt' => $schema
                ->string()
                ->description('Natural-language instructions to steer which pages are crawled, e.g. "only blog posts".')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $url = $request->string('url')->trim();

        if ($url->isEmpty()) {
            return 'The URL is empty. Provide a URL to crawl.';
        }

        $apiKey = $this->firecrawlApiKey();

        if ($apiKey === null) {
            return $this->firecrawlNotConfiguredMessage();
        }

        $payload = [
            'url' => (string) $url,
            'limit' => $this->firecrawlResolveLimit($request, 'ai.toolkit.firecrawl.crawl.limit', 10, 1, 1000),
        ];

        $prompt = $request->string('prompt')->trim();

        if ($prompt->isNotEmpty()) {
            $payload['prompt'] = (string) $prompt;
        }

        return $this->firecrawlPost($apiKey, 30, '/v2/crawl', $payload, 'crawl');
    }
}
