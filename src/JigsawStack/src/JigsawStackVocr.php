<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackVocr implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Run vision OCR on an image or PDF using JigsawStack to read and
            describe its contents. Optionally pass a prompt to extract specific
            fields.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the image or PDF to read')
                ->required(),
            'prompt' => $schema
                ->string()
                ->description("What to extract or how to analyze the image (default: 'Describe the image in detail.')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('url')) {
            return 'The URL is empty. Provide an image or PDF URL to read.';
        }

        $url = trim((string) $request->string('url'));

        $payload = $this->withOptional($request, ['url' => $url], 'prompt');

        return $this->request('post', 'v1/vocr', $payload);
    }
}
