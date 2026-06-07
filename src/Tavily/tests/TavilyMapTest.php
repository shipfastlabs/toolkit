<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Tavily\TavilyMap;

it('has a description', function (): void {
    expect((new TavilyMap)->description())->toContain('Map the structure of a website');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new TavilyMap))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new TavilyMap)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('instructions')
        ->and($schema)->toHaveKey('max_depth')
        ->and($schema)->toHaveKey('max_breadth')
        ->and($schema)->toHaveKey('limit')
        ->and($schema)->toHaveKey('allow_external');
});

it('returns an error when url is empty', function (): void {
    $result = (new TavilyMap)->handle(new Request(['url' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.tavily.key');

    $result = (new TavilyMap)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('not configured');
});

it('returns map results on success', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake([
        'https://api.tavily.com/map' => Http::response([
            'base_url' => 'https://example.com',
            'results' => [
                'https://example.com/page1',
                'https://example.com/page2',
            ],
        ]),
    ]);

    $result = (new TavilyMap)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('https://example.com/page1')
        ->and($result)->toContain('https://example.com/page2');
});

it('uses bearer token for authentication', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->header('Authorization'))->toContain('Bearer test-key');

        return Http::response([
            'base_url' => 'https://example.com',
            'results' => [],
        ]);
    });

    (new TavilyMap)->handle(new Request(['url' => 'https://example.com']));
});

it('respects custom map parameters', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('max_depth', 3)
            ->toHaveKey('max_breadth', 50)
            ->toHaveKey('limit', 100)
            ->toHaveKey('allow_external', false);

        return Http::response([
            'base_url' => 'https://example.com',
            'results' => [],
        ]);
    });

    (new TavilyMap)->handle(new Request([
        'url' => 'https://example.com',
        'max_depth' => 3,
        'max_breadth' => 50,
        'limit' => 100,
        'allow_external' => false,
    ]));
});
