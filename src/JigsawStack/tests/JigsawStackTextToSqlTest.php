<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackTextToSql;

it('has a description', function (): void {
    expect((new JigsawStackTextToSql)->description())->toContain('SQL');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackTextToSql))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackTextToSql)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('prompt')
        ->and($schema)->toHaveKey('sql_schema')
        ->and($schema)->toHaveKey('database');
});

it('returns an error when the prompt is empty', function (): void {
    expect((new JigsawStackTextToSql)->handle(new Request(['prompt' => ' '])))->toContain('empty');
});

it('forwards the prompt, schema and database', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'sql' => 'SELECT count(*) FROM users'])]);

    $result = (new JigsawStackTextToSql)->handle(new Request([
        'prompt' => 'How many users signed up today?',
        'sql_schema' => 'CREATE TABLE users (id int)',
        'database' => 'postgresql',
    ]));

    expect($result)->toContain('SELECT');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/sql')
            ->and($request->data())->toMatchArray([
                'prompt' => 'How many users signed up today?',
                'sql_schema' => 'CREATE TABLE users (id int)',
                'database' => 'postgresql',
            ]);

        return true;
    });
});
