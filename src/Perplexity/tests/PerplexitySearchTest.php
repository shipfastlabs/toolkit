<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Perplexity\PerplexitySearch;

it('has a description', function (): void {
    expect((new PerplexitySearch)->description())->toContain('Search the web');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new PerplexitySearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new PerplexitySearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('max_results')
        ->and($schema)->toHaveKey('search_recency_filter');
});

it('returns an error when the query is empty', function (): void {
    $result = (new PerplexitySearch)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.perplexity.key');

    $result = (new PerplexitySearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('not configured');
});

it('returns search results on success', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake([
        'https://api.perplexity.ai/search' => Http::response([
            'results' => [
                [
                    'title' => 'Laravel 11 Released',
                    'url' => 'https://laravel.com',
                    'snippet' => 'Laravel 11 is here with new features.',
                ],
            ],
        ]),
    ]);

    $result = (new PerplexitySearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('Laravel 11 Released')
        ->and($result)->toContain('Laravel 11 is here');
});

it('sends the api key as a bearer token', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->hasHeader('Authorization', 'Bearer test-key'))->toBeTrue();

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test']));
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake([
        'https://api.perplexity.ai/search' => Http::response('Invalid API key', 401),
    ]);

    $result = (new PerplexitySearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('failed with status 401');
});

it('returns an error when the response is not an array', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake([
        'https://api.perplexity.ai/search' => Http::response('"plain string"', 200, ['Content-Type' => 'application/json']),
    ]);

    $result = (new PerplexitySearch)->handle(new Request(['query' => 'test']));

    expect($result)->toContain('invalid');
});

it('respects custom max_results', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('max_results', 3);

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test', 'max_results' => 3]));
});

it('applies a valid recency filter', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search_recency_filter', 'week');

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test', 'search_recency_filter' => 'week']));
});

it('omits an invalid recency filter', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->not->toHaveKey('search_recency_filter');

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test', 'search_recency_filter' => 'decade']));
});

it('uses the configured default recency when omitted', function (): void {
    config()->set('services.perplexity.key', 'test-key');
    config()->set('ai.toolkit.perplexity.search.recency', 'month');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search_recency_filter', 'month');

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test']));
});

it('uses defaults when optional params are omitted', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('max_results', 10)
            ->not->toHaveKey('search_recency_filter');

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test']));
});

it('uses defaults when optional params are explicitly null', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('max_results', 10)
            ->not->toHaveKey('search_recency_filter');

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request([
        'query' => 'test',
        'max_results' => null,
        'search_recency_filter' => null,
    ]));
});

it('falls back to the default when configured max_results is not numeric', function (): void {
    config()->set('services.perplexity.key', 'test-key');
    config()->set('ai.toolkit.perplexity.search.max_results', 'not-a-number');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('max_results', 10);

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test']));
});

it('clamps max_results between 1 and 20', function (int $input, int $expected): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('max_results', $expected);

        return Http::response(['results' => []]);
    });

    (new PerplexitySearch)->handle(new Request(['query' => 'test', 'max_results' => $input]));
})->with([
    'too low' => [-5, 1],
    'zero' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [20, 20],
    'too high' => [50, 20],
]);

it('returns a friendly error when the request throws', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function (): void {
        throw new RuntimeException('Connection timed out');
    });

    $result = (new PerplexitySearch)->handle(new Request(['query' => 'test']));

    expect($result)->toContain('request failed')
        ->and($result)->toContain('Connection timed out');
});
