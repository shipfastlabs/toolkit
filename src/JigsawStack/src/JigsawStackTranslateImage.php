<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackTranslateImage implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Extract and translate the text found in an image using JigsawStack.
            Returns a URL to the translated image.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the image to translate')
                ->required(),
            'target_language' => $schema
                ->string()
                ->description('The language code to translate the image text into, e.g. "es", "fr", "de"')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('url')) {
            return 'The URL is empty. Provide an image URL to translate.';
        }

        if (! $request->filled('target_language')) {
            return 'The target_language is empty. Provide a language code to translate into.';
        }

        return $this->request('post', 'v1/ai/translate/image', [
            'url' => trim((string) $request->string('url')),
            'target_language' => trim((string) $request->string('target_language')),
            'return_type' => 'url',
        ]);
    }
}
