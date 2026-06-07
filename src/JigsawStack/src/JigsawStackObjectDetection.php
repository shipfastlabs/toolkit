<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackObjectDetection implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Detect and locate objects within an image using JigsawStack.
            Optionally provide prompts to target specific objects to find.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the image to analyze')
                ->required(),
            'prompts' => $schema
                ->string()
                ->description('A comma-separated list of objects to detect. Detects all objects when omitted.')
                ->nullable()
                ->required(),
            'annotated_image' => $schema
                ->boolean()
                ->description('Whether to return an annotated copy of the image (default: false)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('url')) {
            return 'The URL is empty. Provide an image URL to analyze.';
        }

        $payload = ['url' => trim((string) $request->string('url'))];

        if ($request->filled('prompts')) {
            $prompts = $this->splitList((string) $request->string('prompts'));

            if (filled($prompts)) {
                $payload['prompts'] = $prompts;
            }
        }

        $payload = $this->withOptional($request, $payload, 'annotated_image');

        return $this->request('post', 'v1/object_detection', $payload);
    }
}
