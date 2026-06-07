<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackAiScrape implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Extract structured data from a web page using JigsawStack AI scraping.
            Describe the fields you want in plain language and the tool returns the
            matching content from the page.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the web page to scrape')
                ->required(),
            'element_prompts' => $schema
                ->string()
                ->description('A comma-separated list of plain-language descriptions of the data to extract (maximum 5)')
                ->required(),
            'root_element_selector' => $schema
                ->string()
                ->description("A CSS selector to scope scraping to part of the page (default: 'main')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('url')) {
            return 'The URL is empty. Provide a URL to scrape.';
        }

        if (! $request->filled('element_prompts')) {
            return 'The element_prompts are empty. Describe what to scrape.';
        }

        $prompts = $this->splitList((string) $request->string('element_prompts'));

        if (blank($prompts)) {
            return 'No valid element prompts were provided.';
        }

        $payload = $this->withOptional($request, [
            'url' => trim((string) $request->string('url')),
            'element_prompts' => $prompts,
        ], 'root_element_selector');

        return $this->request('post', 'v1/ai/scrape', $payload);
    }
}
