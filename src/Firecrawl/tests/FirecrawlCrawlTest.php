<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlCrawl;

beforeEach(function (): void {
    config()->set('services.firecrawl.key', 'test-key');

    Http::preventStrayRequests();
});

it('has a description that mentions it is asynchronous', function (): void {
    expect((new FirecrawlCrawl)->description())->toContain('asynchronously');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new FirecrawlCrawl))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new FirecrawlCrawl)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('limit')
        ->and($schema)->toHaveKey('prompt');
});

it('returns an error when the url is empty', function (): void {
    $result = (new FirecrawlCrawl)->handle(new Request(['url' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.firecrawl.key');

    $result = (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('not configured');
});

it('returns the crawl id on success', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/crawl' => Http::response([
            'success' => true,
            'id' => 'abc-123',
            'url' => 'https://api.firecrawl.dev/v2/crawl/abc-123',
        ]),
    ]);

    $result = (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('abc-123');
});

it('defaults the limit to 10 when omitted', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('limit', 10);

        return Http::response(['success' => true, 'id' => 'abc']);
    });

    (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com']));
});

it('uses the configured default limit when omitted', function (): void {
    config()->set('ai.toolkit.firecrawl.crawl.limit', 25);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('limit', 25);

        return Http::response(['success' => true, 'id' => 'abc']);
    });

    (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com']));
});

it('clamps the limit between 1 and 1000', function (int $input, int $expected): void {
    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('limit', $expected);

        return Http::response(['success' => true, 'id' => 'abc']);
    });

    (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com', 'limit' => $input]));
})->with([
    'too low' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [1000, 1000],
    'too high' => [5000, 1000],
]);

it('sends a prompt when provided', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('prompt', 'only blog posts');

        return Http::response(['success' => true, 'id' => 'abc']);
    });

    (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com', 'prompt' => ' only blog posts ']));
});

it('omits the prompt when not provided', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->not->toHaveKey('prompt');

        return Http::response(['success' => true, 'id' => 'abc']);
    });

    (new FirecrawlCrawl)->handle(new Request(['url' => 'https://example.com']));
});
