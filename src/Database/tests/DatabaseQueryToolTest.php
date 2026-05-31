<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Database\DatabaseQueryTool;

function runQuery(string $query): string
{
    return (new DatabaseQueryTool)->handle(new Request(['query' => $query]));
}

beforeEach(function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    Schema::create('orders', function ($table): void {
        $table->increments('id');
        $table->string('status');
    });

    DB::table('orders')->insert([
        ['status' => 'paid'],
        ['status' => 'paid'],
        ['status' => 'pending'],
    ]);
});

it('has a description and is strict', function (): void {
    expect((new DatabaseQueryTool)->description())->toContain('read-only')
        ->and(Strict::isAppliedTo(new DatabaseQueryTool))->toBeTrue();
});

it('exposes a required query schema', function (): void {
    $schema = (new DatabaseQueryTool)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema['query']->toArray())->toMatchArray(['type' => 'string']);
});

it('returns rows for a select query', function (): void {
    $result = runQuery('SELECT COUNT(*) as total FROM orders WHERE status = "paid"');

    expect($result)->toContain('"total": 2');
});

it('caps the number of returned rows', function (): void {
    config()->set('ai.toolkit.database.max_rows', 2);

    $rows = json_decode(runQuery('SELECT * FROM orders'), true);

    expect($rows)->toHaveCount(2);
});

it('reports when no rows match', function (): void {
    expect(runQuery('SELECT * FROM orders WHERE status = "refunded"'))
        ->toContain('no rows');
});

it('rejects non-select statements', function (string $query): void {
    expect(runQuery($query))->toContain('Only read-only SELECT queries are allowed');
})->with([
    'update' => ['UPDATE orders SET status = "paid"'],
    'delete' => ['DELETE FROM orders'],
    'drop' => ['DROP TABLE orders'],
    'insert' => ['INSERT INTO orders (status) VALUES ("x")'],
]);

it('rejects a select that hides a forbidden keyword', function (): void {
    expect(runQuery("SELECT * FROM orders WHERE status = 'please update now'"))
        ->toContain('disallowed keyword [UPDATE]');
});

it('respects an explicit limit in the query', function (): void {
    config()->set('ai.toolkit.database.max_rows', 100);

    $rows = json_decode(runQuery('SELECT * FROM orders LIMIT 1'), true);

    expect($rows)->toHaveCount(1);
});

it('runs against an explicitly configured connection', function (): void {
    config()->set('ai.toolkit.database.connection', 'testing');

    expect(runQuery('SELECT COUNT(*) as total FROM orders'))->toContain('"total": 3');
});

it('rejects multiple statements', function (): void {
    expect(runQuery('SELECT * FROM orders; DROP TABLE orders'))
        ->toContain('single statement');
});

it('rejects an empty query', function (): void {
    expect(runQuery('   '))->toContain('empty');
});

it('returns a friendly error for invalid sql', function (): void {
    expect(runQuery('SELECT * FROM missing_table'))->toContain('The query failed');
});
