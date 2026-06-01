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
class TavilyMap implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Tavily Map - Website structure discovery and mapping.
            Traverse a website like a graph to discover all pages and return
            the list of URLs as JSON. Use this to understand a site's structure,
            e.g. "map https://docs.laravel.com".
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The root URL to begin the mapping from.')
                ->required(),
            'instructions' => $schema
                ->string()
                ->description('Optional natural language instructions for the crawler.')
                ->nullable(),
            'max_depth' => $schema
                ->integer()
                ->description('Maximum map depth (1-5). Defaults to 1.')
                ->nullable(),
            'max_breadth' => $schema
                ->integer()
                ->description('Maximum links to follow per page (1-500). Defaults to 20.')
                ->nullable(),
            'limit' => $schema
                ->integer()
                ->description('Total number of links to process before stopping. Defaults to 50.')
                ->nullable(),
            'allow_external' => $schema
                ->boolean()
                ->description('Whether to include external domain links. Defaults to true.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $url = trim((string) $request->string('url'));

        if ($url === '') {
            return 'The URL is empty. Provide a root URL to map.';
        }

        $apiKey = config('services.tavily.key');

        if (! is_string($apiKey) || $apiKey === '') {
            return 'The Tavily tool is not configured. Set services.tavily.key in your config/services.php file.';
        }

        $payload = ['url' => $url];

        if ($request->has('instructions') && $request['instructions'] !== null) {
            $payload['instructions'] = (string) $request->string('instructions');
        }

        $maxDepth = $request->has('max_depth') && $request['max_depth'] !== null
            ? max(1, min(5, $request->integer('max_depth')))
            : $this->defaultMaxDepth();
        $payload['max_depth'] = $maxDepth;

        $maxBreadth = $request->has('max_breadth') && $request['max_breadth'] !== null
            ? max(1, min(500, $request->integer('max_breadth')))
            : $this->defaultMaxBreadth();
        $payload['max_breadth'] = $maxBreadth;

        $limit = $request->has('limit') && $request['limit'] !== null
            ? max(1, $request->integer('limit'))
            : $this->defaultLimit();
        $payload['limit'] = $limit;

        if ($request->has('allow_external') && $request['allow_external'] !== null) {
            $payload['allow_external'] = $request->boolean('allow_external');
        }

        try {
            $response = Http::timeout(150)
                ->withToken($apiKey)
                ->post('https://api.tavily.com/map', $payload);
        } catch (Throwable $throwable) {
            return sprintf('The Tavily map request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Tavily map request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return 'The Tavily map response was invalid.';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function defaultMaxDepth(): int
    {
        $depth = config('ai.toolkit.tavily.map.max_depth', 1);

        return is_numeric($depth) ? max(1, min(5, (int) $depth)) : 1;
    }

    private function defaultMaxBreadth(): int
    {
        $breadth = config('ai.toolkit.tavily.map.max_breadth', 20);

        return is_numeric($breadth) ? max(1, min(500, (int) $breadth)) : 20;
    }

    private function defaultLimit(): int
    {
        $limit = config('ai.toolkit.tavily.map.limit', 50);

        return is_numeric($limit) ? max(1, (int) $limit) : 50;
    }
}
