<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlScrape;

beforeEach(function (): void {
    config()->set('services.firecrawl.key', 'test-key');

    Http::preventStrayRequests();
});

it('has a description', function (): void {
    expect((new FirecrawlScrape)->description())->toContain('Scrape');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new FirecrawlScrape))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new FirecrawlScrape)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('formats')
        ->and($schema)->toHaveKey('only_main_content');
});

it('returns an error when the url is empty', function (): void {
    $result = (new FirecrawlScrape)->handle(new Request(['url' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.firecrawl.key');

    $result = (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('not configured');
});

it('returns the scraped content on success', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/scrape' => Http::response(['success' => true, 'data' => ['markdown' => '# Example']]),
    ]);

    $result = (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('# Example');
});

it('sends the api key as a bearer token', function (): void {
    Http::fake(function ($request) {
        expect($request->hasHeader('Authorization', 'Bearer test-key'))->toBeTrue();

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});

it('returns an error when the api responds with a failure', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/scrape' => Http::response('Unauthorized', 401),
    ]);

    $result = (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('failed with status 401');
});

it('returns an error when the response is not an array', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/scrape' => Http::response('"plain string"', 200, ['Content-Type' => 'application/json']),
    ]);

    $result = (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('invalid');
});

it('returns a friendly error when the request throws', function (): void {
    Http::fake(function (): void {
        throw new RuntimeException('Connection timed out');
    });

    $result = (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));

    expect($result)->toContain('request failed')
        ->and($result)->toContain('Connection timed out');
});

it('defaults to markdown when formats are omitted', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('formats', ['markdown']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});

it('parses comma-separated formats', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('formats', ['markdown', 'links']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com', 'formats' => 'markdown, links']));
});

it('drops invalid formats and falls back to markdown', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('formats', ['markdown']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com', 'formats' => 'bogus, nope']));
});

it('uses the configured default formats when omitted', function (): void {
    config()->set('ai.toolkit.firecrawl.scrape.formats', 'html,links');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('formats', ['html', 'links']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});

it('falls back to markdown when the configured formats are not a string', function (): void {
    config()->set('ai.toolkit.firecrawl.scrape.formats', 123);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('formats', ['markdown']);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});

it('defaults only_main_content to true', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('onlyMainContent', true);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});

it('respects an explicit only_main_content of false', function (): void {
    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('onlyMainContent', false);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com', 'only_main_content' => false]));
});

it('uses the configured default only_main_content when omitted', function (): void {
    config()->set('ai.toolkit.firecrawl.scrape.only_main_content', false);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('onlyMainContent', false);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});

it('falls back to true when the configured only_main_content is not a boolean', function (): void {
    config()->set('ai.toolkit.firecrawl.scrape.only_main_content', 'yes');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('onlyMainContent', true);

        return Http::response(['success' => true, 'data' => []]);
    });

    (new FirecrawlScrape)->handle(new Request(['url' => 'https://example.com']));
});
