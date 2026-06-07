<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Exa;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\Concerns\InteractsWithExa;

#[Strict]
class ExaGetContents implements Tool
{
    use InteractsWithExa;

    public function description(): string
    {
        return <<<'DESCRIPTION'
            Retrieve clean, parsed contents from one or more URLs using Exa. Returns full page text and optionally AI-generated summaries and relevant highlights, with control over fresh (live) crawling.
            DESCRIPTION;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'urls' => $schema
                ->string()
                ->description('A single URL or comma-separated list of URLs to fetch contents from')
                ->required(),
            'text' => $schema
                ->boolean()
                ->description('Get the page text content (default: true)')
                ->nullable()
                ->required(),
            'summary' => $schema
                ->boolean()
                ->description('Get an AI-generated summary of each page (default: false)')
                ->nullable()
                ->required(),
            'highlights' => $schema
                ->boolean()
                ->description('Get text snippets the LLM identifies as most relevant from each page (default: false)')
                ->nullable()
                ->required(),
            'livecrawl' => $schema
                ->string()
                ->description("Livecrawl mode - 'never', 'fallback', 'always', or 'preferred' (default: 'fallback')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $urlsInput = trim((string) $request->string('urls'));

        if ($urlsInput === '') {
            return 'The URLs are empty. Provide at least one URL to fetch contents from.';
        }

        $urls = array_values(array_filter(
            array_map(trim(...), explode(',', $urlsInput)),
            static fn (string $url): bool => $url !== '',
        ));

        if ($urls === []) {
            return 'No valid URLs were provided.';
        }

        if (count($urls) > 20) {
            return 'A maximum of 20 URLs is allowed per request.';
        }

        $apiKey = $this->exaApiKey();

        if ($apiKey === null) {
            return $this->exaNotConfiguredMessage();
        }

        $payload = ['urls' => $urls];

        $text = $request->has('text') && $request['text'] !== null
            ? $request->boolean('text')
            : $this->defaultText();
        $payload['text'] = $text;

        if ($request->has('summary') && $request['summary'] !== null && $request->boolean('summary')) {
            $payload['summary'] = true;
        }

        if ($request->has('highlights') && $request['highlights'] !== null && $request->boolean('highlights')) {
            $payload['highlights'] = true;
        }

        $livecrawl = $request->has('livecrawl') && $request['livecrawl'] !== null
            ? $this->validateLivecrawl((string) $request->string('livecrawl'))
            : $this->defaultLivecrawl();
        $payload['livecrawl'] = $livecrawl;

        return $this->exaRequest($apiKey, 60, '/contents', $payload, 'contents');
    }

    private function defaultText(): bool
    {
        return (bool) config('ai.toolkit.exa.contents.text', true);
    }

    private function defaultLivecrawl(): string
    {
        $mode = config('ai.toolkit.exa.contents.livecrawl', 'fallback');

        return $this->validateLivecrawl(is_string($mode) ? $mode : 'fallback');
    }

    private function validateLivecrawl(string $mode): string
    {
        return in_array($mode, ['never', 'fallback', 'always', 'preferred'], true) ? $mode : 'fallback';
    }
}
