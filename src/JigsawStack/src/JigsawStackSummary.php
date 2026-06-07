<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackSummary implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Summarize long text into a concise paragraph or bullet points using
            JigsawStack. Use this to condense articles, documents, or transcripts.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text to summarize (maximum 300,000 characters)')
                ->required(),
            'type' => $schema
                ->string()
                ->description("Output format - 'text' for a paragraph or 'points' for bullet points (default: 'text')")
                ->nullable()
                ->required(),
            'max_points' => $schema
                ->integer()
                ->description("Maximum number of bullet points when type is 'points' (1-100, default: 2)")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to summarize.';
        }

        $text = trim((string) $request->string('text'));

        $payload = $this->withOptional($request, ['text' => $text], 'type', 'max_points');

        return $this->request('post', 'v1/ai/summary', $payload);
    }
}
