<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Database;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class DatabaseQueryTool implements Tool
{
    /**
     * @var list<string>
     */
    private const array FORBIDDEN_KEYWORDS = [
        'insert', 'update', 'delete', 'drop', 'truncate', 'alter', 'create',
        'replace', 'grant', 'revoke', 'attach', 'detach', 'pragma', 'merge',
        'call', 'execute', 'exec', 'into', 'lock', 'rename', 'set',
    ];

    public function description(): string
    {
        return <<<'TXT'
            Run a read-only SQL SELECT query against the application database and return the matching rows
            as JSON. Use this to answer questions about the data, e.g. "how many orders were placed today".
            Only a single SELECT statement is allowed; any statement that writes or changes data is rejected.
            Results are limited to a maximum number of rows.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema
                ->string()
                ->description('A single read-only SQL SELECT statement to execute.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $query = trim((string) $request->string('query'));
        $query = rtrim($query, ';');

        if ($query === '') {
            return 'The query is empty. Provide a SELECT statement.';
        }

        if (Str::contains($query, ';')) {
            return 'Only a single statement may be executed. Remove the additional ";" separators.';
        }

        if (! $this->isSelect($query)) {
            return 'Only read-only SELECT queries are allowed.';
        }

        if ($keyword = $this->forbiddenKeyword($query)) {
            return sprintf('The query contains the disallowed keyword [%s]. Only read-only SELECT queries are allowed.', $keyword);
        }

        try {
            $rows = DB::connection($this->connection())
                ->select($this->limit($query));
        } catch (Throwable $throwable) {
            return 'The query failed: '.$throwable->getMessage();
        }

        if ($rows === []) {
            return 'The query returned no rows.';
        }

        return json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    private function isSelect(string $query): bool
    {
        return (bool) preg_match('/^\s*(select|with)\b/i', $query);
    }

    private function forbiddenKeyword(string $query): ?string
    {
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $query)) {
                return Str::upper($keyword);
            }
        }

        return null;
    }

    private function limit(string $query): string
    {
        $max = $this->maxRows();

        if (preg_match('/\blimit\b/i', $query)) {
            return $query;
        }

        return $query.' LIMIT '.$max;
    }

    private function connection(): ?string
    {
        /** @var string|null $connection */
        $connection = config('ai.toolkit.database.connection');

        return $connection;
    }

    private function maxRows(): int
    {
        $max = config('ai.toolkit.database.max_rows', 100);

        return is_numeric($max) ? (int) $max : 100;
    }
}
