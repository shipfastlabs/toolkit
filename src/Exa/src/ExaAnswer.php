<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Exa;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\Concerns\InteractsWithExa;

#[Strict]
class ExaAnswer implements Tool
{
    use InteractsWithExa;

    public function description(): string
    {
        return <<<'DESCRIPTION'
            Get a direct, sourced answer to a question using Exa. Exa searches the web, reads the most relevant pages, and returns a concise answer together with the citations it was based on.
            DESCRIPTION;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('The question to answer using web sources')
                ->required(),
            'include_text' => $schema
                ->boolean()
                ->description('Whether to include the full text of each cited source in the response (default: false)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $query = trim((string) $request->string('query'));

        if ($query === '') {
            return 'The question is empty. Provide a question to answer.';
        }

        $apiKey = $this->exaApiKey();

        if ($apiKey === null) {
            return $this->exaNotConfiguredMessage();
        }

        $payload = ['query' => $query];

        if ($this->exaIncludeText($request, 'ai.toolkit.exa.answer.include_text', false)) {
            $payload['text'] = true;
        }

        return $this->exaRequest($apiKey, 60, '/answer', $payload, 'answer');
    }
}
