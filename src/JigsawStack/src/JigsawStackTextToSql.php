<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackTextToSql implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Convert a natural-language question into an SQL query using
            JigsawStack. Provide the database schema so the generated query
            references the correct tables and columns.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'prompt' => $schema
                ->string()
                ->description('The natural-language question to convert into SQL (minimum 10 characters)')
                ->required(),
            'sql_schema' => $schema
                ->string()
                ->description('The database schema (CREATE TABLE statements) the query should run against')
                ->nullable()
                ->required(),
            'database' => $schema
                ->string()
                ->description("The target database for improved accuracy - 'postgresql', 'mysql', or 'sqlite'")
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('prompt')) {
            return 'The prompt is empty. Provide a question to convert into SQL.';
        }

        $prompt = trim((string) $request->string('prompt'));

        $payload = $this->withOptional($request, ['prompt' => $prompt], 'sql_schema', 'database');

        return $this->request('post', 'v1/ai/sql', $payload);
    }
}
