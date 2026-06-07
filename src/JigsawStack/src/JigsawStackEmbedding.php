<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackEmbedding implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Generate vector embeddings for text using JigsawStack. Use the
            resulting vectors for semantic search, clustering, or retrieval.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text content to embed')
                ->required(),
            'type' => $schema
                ->string()
                ->description("The content type to embed - 'text' or 'text-other' (default: 'text')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to embed.';
        }

        $text = trim((string) $request->string('text'));
        $type = $request->filled('type') ? (string) $request->string('type') : 'text';

        return $this->request('post', 'v1/embedding', [
            'type' => $type,
            'text' => $text,
        ]);
    }
}
