<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Tavily\TavilyCrawl;

it('has a description', function (): void {
    expect((new TavilyCrawl)->description())->toContain('Tavily Crawl');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new TavilyCrawl))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new TavilyCrawl)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('instructions')
        ->and($schema)->toHaveKey('max_depth')
        ->and($schema)->toHaveKey('max_breadth')
        ->and($schema)->toHaveKey('limit')
        ->and($schema)->toHaveKey('extract_depth')
        ->and($schema)->toHaveKey('allow_external');
});

it('returns an error when url is empty', function (): void {
    $result = (new TavilyCrawl)->handle(new Request(['url' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.tavily.key');

    $result = (new TavilyCrawl)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('not configured');
});

it('returns crawl results on success', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake([
        'https://api.tavily.com/crawl' => Http::response([
            'base_url' => 'https://example.com',
            'results' => [
                ['url' => 'https://example.com/page1', 'raw_content' => 'Page 1'],
            ],
        ]),
    ]);

    $result = (new TavilyCrawl)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('Page 1');
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

    (new TavilyCrawl)->handle(new Request(['url' => 'https://example.com']));
});

it('respects custom crawl parameters', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('max_depth', 2)
            ->toHaveKey('max_breadth', 10)
            ->toHaveKey('limit', 25)
            ->toHaveKey('extract_depth', 'advanced')
            ->toHaveKey('allow_external', false);

        return Http::response([
            'base_url' => 'https://example.com',
            'results' => [],
        ]);
    });

    (new TavilyCrawl)->handle(new Request([
        'url' => 'https://example.com',
        'max_depth' => 2,
        'max_breadth' => 10,
        'limit' => 25,
        'extract_depth' => 'advanced',
        'allow_external' => false,
    ]));
});

it('clamps max_depth between 1 and 5', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('max_depth', 1);

        return Http::response([
            'base_url' => 'https://example.com',
            'results' => [],
        ]);
    });

    (new TavilyCrawl)->handle(new Request(['url' => 'https://example.com', 'max_depth' => 0]));
});

it('clamps max_breadth between 1 and 500', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('max_breadth', 500);

        return Http::response([
            'base_url' => 'https://example.com',
            'results' => [],
        ]);
    });

    (new TavilyCrawl)->handle(new Request(['url' => 'https://example.com', 'max_breadth' => 600]));
});
