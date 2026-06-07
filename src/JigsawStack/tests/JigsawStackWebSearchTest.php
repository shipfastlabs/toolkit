<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackWebSearch;

it('has a description', function (): void {
    expect((new JigsawStackWebSearch)->description())->toContain('Search the web');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackWebSearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackWebSearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('ai_overview')
        ->and($schema)->toHaveKey('safe_search')
        ->and($schema)->toHaveKey('max_results');
});

it('returns an error when the query is empty', function (): void {
    expect((new JigsawStackWebSearch)->handle(new Request(['query' => ' '])))->toContain('empty');
});

it('forwards optional parameters when provided', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'results' => []])]);

    (new JigsawStackWebSearch)->handle(new Request([
        'query' => 'laravel news',
        'ai_overview' => false,
        'safe_search' => 'strict',
        'max_results' => 5,
    ]));

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/web/search')
            ->and($request->data())->toMatchArray([
                'query' => 'laravel news',
                'ai_overview' => false,
                'safe_search' => 'strict',
                'max_results' => 5,
            ]);

        return true;
    });
});

it('searches with only a query', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'results' => [['title' => 'Laravel']]])]);

    $result = (new JigsawStackWebSearch)->handle(new Request(['query' => 'laravel news']));

    expect($result)->toContain('Laravel');

    Http::assertSent(function ($request): true {
        expect($request->data())->toBe(['query' => 'laravel news']);

        return true;
    });
});
