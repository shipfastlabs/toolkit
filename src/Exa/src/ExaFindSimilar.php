<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Exa;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\Concerns\InteractsWithExa;

#[Strict]
class ExaFindSimilar implements Tool
{
    use InteractsWithExa;

    public function description(): string
    {
        return <<<'DESCRIPTION'
            Find web pages semantically similar to a given URL using Exa. Given a link, returns other pages with related content - ideal for discovering competitors, related research, or alternative sources.
            DESCRIPTION;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL to find similar pages for')
                ->required(),
            'num_results' => $schema
                ->integer()
                ->description('Number of similar results to return (1-25, default: 10)')
                ->nullable()
                ->required(),
            'include_domains' => $schema
                ->string()
                ->description('Comma-separated list of domains to restrict results to')
                ->nullable()
                ->required(),
            'exclude_domains' => $schema
                ->string()
                ->description('Comma-separated list of domains to exclude from results')
                ->nullable()
                ->required(),
            'include_text' => $schema
                ->boolean()
                ->description('Whether to include the full page text of each result (default: true)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $url = trim((string) $request->string('url'));

        if ($url === '') {
            return 'The URL is empty. Provide a URL to find similar pages for.';
        }

        $apiKey = $this->exaApiKey();

        if ($apiKey === null) {
            return $this->exaNotConfiguredMessage();
        }

        $payload = [
            'url' => $url,
            'numResults' => $this->exaNumResults($request, 'ai.toolkit.exa.find_similar.num_results'),
        ];

        $includeDomains = $this->parseDomains($request, 'include_domains');

        if ($includeDomains !== []) {
            $payload['includeDomains'] = $includeDomains;
        }

        $excludeDomains = $this->parseDomains($request, 'exclude_domains');

        if ($excludeDomains !== []) {
            $payload['excludeDomains'] = $excludeDomains;
        }

        if ($this->exaIncludeText($request, 'ai.toolkit.exa.find_similar.include_text', true)) {
            $payload['contents'] = ['text' => true];
        }

        return $this->exaRequest($apiKey, 30, '/findSimilar', $payload, 'find similar');
    }
}
