<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlMap;

beforeEach(function (): void {
    config()->set('services.firecrawl.key', 'test-key');

    Http::preventStrayRequests();
});

it('has a description', function (): void {
    expect((new FirecrawlMap)->description())->toContain('Map');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new FirecrawlMap))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new FirecrawlMap)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('search')
        ->and($schema)->toHaveKey('limit');
});

it('returns an error when the url is empty', function (): void {
    $result = (new FirecrawlMap)->handle(new Request(['url' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.firecrawl.key');

    $result = (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('not configured');
});

it('returns the mapped links on success', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/map' => Http::response([
            'success' => true,
            'links' => [['url' => 'https://example.com/', 'title' => 'Example']],
        ]),
    ]);

    $result = (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('https://example.com/')
        ->and($result)->toContain('Example');
});

it('defaults the limit to 100 when omitted', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('limit', 100);

        return Http::response(['success' => true, 'links' => []]);
    });

    (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com']));
});

it('uses the configured default limit when omitted', function (): void {
    config()->set('ai.toolkit.firecrawl.map.limit', 50);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('limit', 50);

        return Http::response(['success' => true, 'links' => []]);
    });

    (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com']));
});

it('falls back to 100 when the configured limit is not numeric', function (): void {
    config()->set('ai.toolkit.firecrawl.map.limit', 'lots');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('limit', 100);

        return Http::response(['success' => true, 'links' => []]);
    });

    (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com']));
});

it('clamps the limit between 1 and 5000', function (int $input, int $expected): void {
    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('limit', $expected);

        return Http::response(['success' => true, 'links' => []]);
    });

    (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com', 'limit' => $input]));
})->with([
    'too low' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [5000, 5000],
    'too high' => [9999, 5000],
]);

it('sends a search filter when provided', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search', 'blog');

        return Http::response(['success' => true, 'links' => []]);
    });

    (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com', 'search' => ' blog ']));
});

it('omits the search filter when not provided', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->not->toHaveKey('search');

        return Http::response(['success' => true, 'links' => []]);
    });

    (new FirecrawlMap)->handle(new Request(['url' => 'https://example.com']));
});
