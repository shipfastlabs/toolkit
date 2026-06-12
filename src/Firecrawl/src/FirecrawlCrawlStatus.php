<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\Concerns\InteractsWithFirecrawl;

#[Strict]
class FirecrawlCrawlStatus implements Tool
{
    use InteractsWithFirecrawl;

    public function description(): string
    {
        return <<<'TXT'
            Check the status of an asynchronous crawl started with the Firecrawl crawl tool.
            Returns the crawl status (scraping or completed), how many pages are done out of
            the total, and the content of the pages crawled so far. Call it again after a
            short wait if the status is still "scraping".
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'crawl_id' => $schema
                ->string()
                ->description('The crawl id returned by the Firecrawl crawl tool.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $crawlId = $request->string('crawl_id')->trim();

        if ($crawlId->isEmpty()) {
            return 'The crawl id is empty. Provide the id returned by the Firecrawl crawl tool.';
        }

        if (preg_match('/^[A-Za-z0-9-]+$/', (string) $crawlId) !== 1) {
            return 'The crawl id is invalid. It should look like the id returned by the Firecrawl crawl tool.';
        }

        $apiKey = $this->firecrawlApiKey();

        if ($apiKey === null) {
            return $this->firecrawlNotConfiguredMessage();
        }

        return $this->firecrawlGet($apiKey, 30, '/v2/crawl/'.$crawlId, 'crawl status');
    }
}
