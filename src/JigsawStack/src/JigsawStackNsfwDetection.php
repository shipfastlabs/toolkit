<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackNsfwDetection implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Detect NSFW content, nudity, or gore in an image using JigsawStack.
            Returns per-category flags and confidence scores.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the image to analyze')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('url')) {
            return 'The URL is empty. Provide an image URL to analyze.';
        }

        $url = trim((string) $request->string('url'));

        return $this->request('post', 'v1/validate/nsfw', ['url' => $url]);
    }
}
