<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\Concerns\InteractsWithFirecrawl;

#[Strict]
class FirecrawlScrape implements Tool
{
    use InteractsWithFirecrawl;

    /**
     * @var list<string>
     */
    private const array FORMATS = ['markdown', 'html', 'rawHtml', 'links', 'summary'];

    public function description(): string
    {
        return <<<'TXT'
            Scrape a single web page and return its content as clean, LLM-ready markdown.
            Use this to read one specific URL. It renders JavaScript, so it works on dynamic
            pages that a plain HTTP fetch cannot read. Optionally request other formats such
            as html, rawHtml, links, or a summary.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the web page to scrape.')
                ->required(),
            'formats' => $schema
                ->string()
                ->description("Comma-separated output formats, any of 'markdown', 'html', 'rawHtml', 'links', 'summary' (default: 'markdown').")
                ->nullable()
                ->required(),
            'only_main_content' => $schema
                ->boolean()
                ->description('Return only the main content, stripping navigation, headers and footers (default: true).')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $url = $request->string('url')->trim();

        if ($url->isEmpty()) {
            return 'The URL is empty. Provide a URL to scrape.';
        }

        $apiKey = $this->firecrawlApiKey();

        if ($apiKey === null) {
            return $this->firecrawlNotConfiguredMessage();
        }

        $payload = [
            'url' => (string) $url,
            'formats' => $this->resolveFormats($request),
            'onlyMainContent' => $this->resolveOnlyMainContent($request),
        ];

        return $this->firecrawlPost($apiKey, 60, '/v2/scrape', $payload, 'scrape');
    }

    /**
     * @return list<string>
     */
    private function resolveFormats(Request $request): array
    {
        $raw = $request->filled('formats')
            ? (string) $request->string('formats')
            : $this->firecrawlConfigString('ai.toolkit.firecrawl.scrape.formats', 'markdown');

        return $this->firecrawlCsvAllowList($raw, self::FORMATS, 'markdown');
    }

    private function resolveOnlyMainContent(Request $request): bool
    {
        if ($request->has('only_main_content') && $request['only_main_content'] !== null) {
            return $request->boolean('only_main_content');
        }

        return $this->firecrawlConfigBool('ai.toolkit.firecrawl.scrape.only_main_content', true);
    }
}
