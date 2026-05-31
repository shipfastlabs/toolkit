<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Stub;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[Strict]
class StubTool implements Tool
{
    public function description(): string
    {
        return 'Describe what this tool does and when the model should use it.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'input' => $schema
                ->string()
                ->description('Describe this input parameter.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $input = (string) $request->string('input');

        return 'You sent: '.$input;
    }
}
