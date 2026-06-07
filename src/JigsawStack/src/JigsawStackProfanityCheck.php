<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackProfanityCheck implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Detect and censor profanity in text using JigsawStack. Returns the
            matched profanities and a cleaned version of the text.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text to check for profanity')
                ->required(),
            'censor_replacement' => $schema
                ->string()
                ->description("The character used to mask profanity (default: '*')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to check for profanity.';
        }

        $text = trim((string) $request->string('text'));

        $payload = $this->withOptional($request, ['text' => $text], 'censor_replacement');

        return $this->request('post', 'v1/validate/profanity', $payload);
    }
}
