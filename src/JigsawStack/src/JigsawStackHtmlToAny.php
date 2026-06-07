<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackHtmlToAny implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Render raw HTML into an image or PDF using JigsawStack. Returns a URL
            to the generated file.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'html' => $schema
                ->string()
                ->description('The HTML markup to render')
                ->required(),
            'type' => $schema
                ->string()
                ->description("The output format - 'png', 'jpeg', 'webp', or 'pdf' (default: 'png')")
                ->nullable()
                ->required(),
            'full_page' => $schema
                ->boolean()
                ->description('Whether to capture the entire scrollable page (default: false)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('html')) {
            return 'The HTML is empty. Provide HTML markup to render.';
        }

        $html = trim((string) $request->string('html'));

        $payload = $this->withOptional($request, [
            'html' => $html,
            'return_type' => 'url',
        ], 'type', 'full_page');

        return $this->request('post', 'v1/web/html_to_any', $payload);
    }
}
