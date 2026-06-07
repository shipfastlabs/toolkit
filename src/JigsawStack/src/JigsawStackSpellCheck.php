<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackSpellCheck implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Detect and correct spelling mistakes in text using JigsawStack.
            Returns the misspelled words and an auto-corrected version of the text.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text to check for spelling mistakes')
                ->required(),
            'language_code' => $schema
                ->string()
                ->description("The language code of the text (default: 'en')")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to spell-check.';
        }

        $text = trim((string) $request->string('text'));

        $payload = $this->withOptional($request, ['text' => $text], 'language_code');

        return $this->request('post', 'v1/validate/spell_check', $payload);
    }
}
