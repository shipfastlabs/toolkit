<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackTranslateText implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Translate text from one language to another using JigsawStack. The
            source language is auto-detected when not provided.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text to translate (maximum 5000 characters)')
                ->required(),
            'target_language' => $schema
                ->string()
                ->description('The language code to translate into, e.g. "es", "fr", "de"')
                ->required(),
            'current_language' => $schema
                ->string()
                ->description('The language code of the source text. Auto-detected when omitted.')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to translate.';
        }

        if (! $request->filled('target_language')) {
            return 'The target_language is empty. Provide a language code to translate into.';
        }

        $payload = $this->withOptional($request, [
            'text' => trim((string) $request->string('text')),
            'target_language' => trim((string) $request->string('target_language')),
        ], 'current_language');

        return $this->request('post', 'v1/ai/translate', $payload);
    }
}
