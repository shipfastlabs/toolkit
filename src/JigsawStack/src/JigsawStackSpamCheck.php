<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackSpamCheck implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Detect whether text is spam using JigsawStack. Returns a spam flag
            and a confidence score.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema
                ->string()
                ->description('The text to check for spam')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('text')) {
            return 'The text is empty. Provide text to check for spam.';
        }

        $text = trim((string) $request->string('text'));

        return $this->request('post', 'v1/validate/spam_check', ['text' => $text]);
    }
}
