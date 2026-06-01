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
class TavilyExtract implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Tavily Extract - Clean, structured content extraction from URLs.
            Extract web page content from one or more URLs and return it as JSON.
            Use this to read the full content of a webpage, e.g. "extract the content
            from https://example.com/article".
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'urls' => $schema
                ->string()
                ->description('A single URL or comma-separated URLs to extract content from.')
                ->required(),
            'query' => $schema
                ->string()
                ->description('Optional query to rerank extracted chunks by relevance.')
                ->nullable(),
            'extract_depth' => $schema
                ->string()
                ->description('Extraction depth: "basic" or "advanced". Defaults to "basic".')
                ->nullable(),
            'format' => $schema
                ->string()
                ->description('Output format: "markdown" or "text". Defaults to "markdown".')
                ->nullable(),
            'include_images' => $schema
                ->boolean()
                ->description('Whether to include a list of images extracted from the URLs. Defaults to false.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $urlsInput = trim((string) $request->string('urls'));

        if ($urlsInput === '') {
            return 'The URLs are empty. Provide at least one URL to extract.';
        }

        $urls = array_filter(array_map(trim(...), explode(',', $urlsInput)));

        if ($urls === []) {
            return 'No valid URLs were provided.';
        }

        if (count($urls) > 20) {
            return 'A maximum of 20 URLs is allowed per request.';
        }

        $apiKey = config('services.tavily.key');

        if (! is_string($apiKey) || $apiKey === '') {
            return 'The Tavily tool is not configured. Set services.tavily.key in your config/services.php file.';
        }

        $payload = [
            'urls' => count($urls) === 1 ? $urls[0] : $urls,
        ];

        if ($request->has('query') && $request['query'] !== null) {
            $payload['query'] = (string) $request->string('query');
        }

        $extractDepth = $request->has('extract_depth') && $request['extract_depth'] !== null
            ? $this->validateExtractDepth((string) $request->string('extract_depth'))
            : $this->defaultExtractDepth();
        $payload['extract_depth'] = $extractDepth;

        $format = $request->has('format') && $request['format'] !== null
            ? $this->validateFormat((string) $request->string('format'))
            : $this->defaultFormat();
        $payload['format'] = $format;

        if ($request->has('include_images') && $request['include_images'] !== null) {
            $payload['include_images'] = $request->boolean('include_images');
        }

        try {
            $response = Http::timeout(30)
                ->withToken($apiKey)
                ->post('https://api.tavily.com/extract', $payload);
        } catch (Throwable $throwable) {
            return sprintf('The Tavily extract request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Tavily extract request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return 'The Tavily extract response was invalid.';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function defaultExtractDepth(): string
    {
        $depth = config('ai.toolkit.tavily.extract.extract_depth', 'basic');

        return $this->validateExtractDepth(is_string($depth) ? $depth : 'basic');
    }

    private function validateExtractDepth(string $depth): string
    {
        return in_array($depth, ['basic', 'advanced'], true) ? $depth : 'basic';
    }

    private function defaultFormat(): string
    {
        $format = config('ai.toolkit.tavily.extract.format', 'markdown');

        return $this->validateFormat(is_string($format) ? $format : 'markdown');
    }

    private function validateFormat(string $format): string
    {
        return in_array($format, ['markdown', 'text'], true) ? $format : 'markdown';
    }
}
