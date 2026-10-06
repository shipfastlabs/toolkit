<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlSearch;

beforeEach(function (): void {
    config()->set('services.firecrawl.key', 'test-key');

    Http::preventStrayRequests();
});

it('has a description', function (): void {
    expect((new FirecrawlSearch)->description())->toContain('Search the web');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new FirecrawlSearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new FirecrawlSearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('limit')
        ->and($schema)->toHaveKey('sources')
        ->and($schema)->toHaveKey('scrape_content');
});

it('returns an error when the query is empty', function (): void {
    $result = (new FirecrawlSearch)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.firecrawl.key');

    $result = (new FirecrawlSearch)->handle(new Request(['query' => 'laravel']));

    expect($result)->toContain('not configured');
});

it('returns the search results on success', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/search' => Http::response([
            'success' => true,
            'data' => ['web' => [['url' => 'https://laravel.com', 'title' => 'Laravel']]],
        ]),
    ]);

    $result = (new FirecrawlSearch)->handle(new Request(['query' => 'laravel']));

    expect($result)->toContain('https://laravel.com')
        ->and($result)->toContain('Laravel');
});

it('defaults the limit to 5 and the source to web', function (): void {
    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('limit', 5)
            ->toHaveKey('sources', ['web'])
            ->not->toHaveKey('scrapeOptions');

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel']));
});

it('uses the configured default limit when omitted', function (): void {
    config()->set('ai.toolkit.firecrawl.search.limit', 8);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('limit', 8);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel']));
});

it('clamps the limit between 1 and 20', function (int $input, int $expected): void {
    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('limit', $expected);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel', 'limit' => $input]));
})->with([
    'too low' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [20, 20],
    'too high' => [99, 20],
]);

it('parses comma-separated sources', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('sources', ['web', 'news']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel', 'sources' => 'web, news']));
});

it('drops invalid sources and falls back to web', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('sources', ['web']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel', 'sources' => 'bogus']));
});

it('uses the configured default sources when omitted', function (): void {
    config()->set('ai.toolkit.firecrawl.search.sources', 'news');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('sources', ['news']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel']));
});

it('adds scrape options when scrape_content is true', function (): void {
    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('scrapeOptions', ['formats' => ['markdown'], 'onlyMainContent' => true]);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel', 'scrape_content' => true]));
});

it('omits scrape options when scrape_content is false', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->not->toHaveKey('scrapeOptions');

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlSearch)->handle(new Request(['query' => 'laravel', 'scrape_content' => false]));
});
