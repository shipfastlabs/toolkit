<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Perplexity;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class PerplexityAsk implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Ask Perplexity a question and get a direct, cited answer grounded in a live
            web search using its Sonar models. Returns the answer text along with the
            search results and source citations used.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The question to ask Perplexity')
                ->required(),
            'model' => $schema
                ->string()
                ->description("The Sonar model to use - one of 'sonar', 'sonar-pro', 'sonar-reasoning', 'sonar-reasoning-pro', or 'sonar-deep-research' (default: 'sonar')")
                ->nullable()
                ->required(),
            'search_mode' => $schema
                ->string()
                ->description("The search mode - 'web' for the open web, 'academic' for scholarly sources, or 'sec' for SEC filings (default: 'web')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $query = $request->string('query')->trim();

        if ($query->isEmpty()) {
            return 'The question is empty. Provide a question to ask Perplexity.';
        }

        $apiKey = config('services.perplexity.key');

        if (! is_string($apiKey) || blank($apiKey)) {
            return 'The Perplexity tool is not configured. Set services.perplexity.key in your config/services.php file.';
        }

        $model = $request->filled('model')
            ? $this->validateModel((string) $request->string('model'))
            : $this->defaultModel();

        $searchMode = $request->filled('search_mode')
            ? $this->validateSearchMode((string) $request->string('search_mode'))
            : $this->defaultSearchMode();

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.perplexity.ai/chat/completions', [
                    'model' => $model,
                    'search_mode' => $searchMode,
                    'messages' => [
                        ['role' => 'user', 'content' => (string) $query],
                    ],
                ]);
        } catch (Throwable $throwable) {
            return sprintf('The Perplexity request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Perplexity request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return 'The Perplexity response was invalid.';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function defaultModel(): string
    {
        $model = config('ai.toolkit.perplexity.ask.model', 'sonar');

        return $this->validateModel(is_string($model) ? $model : 'sonar');
    }

    private function validateModel(string $model): string
    {
        $allowed = ['sonar', 'sonar-pro', 'sonar-reasoning', 'sonar-reasoning-pro', 'sonar-deep-research'];

        return in_array($model, $allowed, true) ? $model : 'sonar';
    }

    private function defaultSearchMode(): string
    {
        $mode = config('ai.toolkit.perplexity.ask.search_mode', 'web');

        return $this->validateSearchMode(is_string($mode) ? $mode : 'web');
    }

    private function validateSearchMode(string $mode): string
    {
        return in_array($mode, ['web', 'academic', 'sec'], true) ? $mode : 'web';
    }
}
