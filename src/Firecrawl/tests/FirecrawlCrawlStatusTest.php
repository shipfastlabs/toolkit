<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlCrawlStatus;

beforeEach(function (): void {
    config()->set('services.firecrawl.key', 'test-key');

    Http::preventStrayRequests();
});

it('has a description', function (): void {
    expect((new FirecrawlCrawlStatus)->description())->toContain('crawl');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new FirecrawlCrawlStatus))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new FirecrawlCrawlStatus)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('crawl_id');
});

it('returns an error when the crawl id is empty', function (): void {
    $result = (new FirecrawlCrawlStatus)->handle(new Request(['crawl_id' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when the crawl id is malformed', function (): void {
    $result = (new FirecrawlCrawlStatus)->handle(new Request(['crawl_id' => '../secrets']));

    expect($result)->toContain('invalid');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.firecrawl.key');

    $result = (new FirecrawlCrawlStatus)->handle(new Request(['crawl_id' => 'abc-123']));

    expect($result)->toContain('not configured');
});

it('returns the crawl status on success', function (): void {
    Http::fake([
        'https://api.firecrawl.dev/v2/crawl/abc-123' => Http::response([
            'success' => true,
            'status' => 'completed',
            'completed' => 1,
            'total' => 1,
            'data' => [['markdown' => '# Page']],
        ]),
    ]);

    $result = (new FirecrawlCrawlStatus)->handle(new Request(['crawl_id' => 'abc-123']));

    expect($result)->toContain('completed')
        ->and($result)->toContain('# Page');
});

it('requests the status endpoint for the given crawl id', function (): void {
    Http::fake(function ($request) {
        expect($request->url())->toBe('https://api.firecrawl.dev/v2/crawl/abc-123')
            ->and($request->method())->toBe('GET');

        return Http::response(['success' => true, 'status' => 'scraping']);
    });

    (new FirecrawlCrawlStatus)->handle(new Request(['crawl_id' => 'abc-123']));
});

it('returns a friendly error when the request throws', function (): void {
    Http::fake(function (): void {
        throw new RuntimeException('Connection timed out');
    });

    $result = (new FirecrawlCrawlStatus)->handle(new Request(['crawl_id' => 'abc-123']));

    expect($result)->toContain('request failed')
        ->and($result)->toContain('Connection timed out');
});
