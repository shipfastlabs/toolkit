<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackSentiment implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Analyze the emotional tone of text using JigsawStack. Returns the
            overall sentiment (positive, negative, or neutral), the dominant
            emotion, a confidence score, and a per-sentence breakdown.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text content to analyze for sentiment and emotion')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to analyze.';
        }

        $text = trim((string) $request->string('text'));

        return $this->request('post', 'v1/ai/sentiment', ['text' => $text]);
    }
}
