<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Tavily\TavilySearch;

it('has a description', function (): void {
    expect((new TavilySearch)->description())->toContain('Search the web');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new TavilySearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new TavilySearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('max_results')
        ->and($schema)->toHaveKey('search_depth')
        ->and($schema)->toHaveKey('include_answer');
});

it('returns an error when the query is empty', function (): void {
    $result = (new TavilySearch)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.tavily.key');

    $result = (new TavilySearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('not configured');
});

it('returns search results on success', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake([
        'https://api.tavily.com/search' => Http::response([
            'query' => 'Laravel news',
            'results' => [
                [
                    'title' => 'Laravel 11 Released',
                    'url' => 'https://laravel.com',
                    'content' => 'Laravel 11 is here with new features.',
                ],
            ],
            'answer' => 'Laravel 11 was released with new features.',
        ]),
    ]);

    $result = (new TavilySearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('Laravel 11 Released')
        ->and($result)->toContain('Laravel 11 is here');
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake([
        'https://api.tavily.com/search' => Http::response('Invalid API key', 401),
    ]);

    $result = (new TavilySearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('failed with status 401');
});

it('respects custom max_results', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('max_results', 3);

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request(['query' => 'test', 'max_results' => 3]));
});

it('respects custom search_depth', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search_depth', 'advanced');

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request(['query' => 'test', 'search_depth' => 'advanced']));
});

it('respects custom include_answer', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('include_answer', true);

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request(['query' => 'test', 'include_answer' => true]));
});

it('uses defaults when optional params are omitted', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('max_results', 5)
            ->toHaveKey('search_depth', 'basic')
            ->toHaveKey('include_answer', false);

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request(['query' => 'test']));
});

it('uses defaults when optional params are explicitly null', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('max_results', 5)
            ->toHaveKey('search_depth', 'basic')
            ->toHaveKey('include_answer', false);

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request([
        'query' => 'test',
        'max_results' => null,
        'search_depth' => null,
        'include_answer' => null,
    ]));
});

it('falls back to basic for invalid search_depth values', function (string $input): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search_depth', 'basic');

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request(['query' => 'test', 'search_depth' => $input]));
})->with([
    'empty' => [''],
    'random' => ['foobar'],
    'wrong case' => ['Advanced'],
]);

it('clamps max_results between 1 and 10', function (int $input, int $expected): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('max_results', $expected);

        return Http::response([
            'query' => 'test',
            'results' => [],
            'answer' => '',
        ]);
    });

    (new TavilySearch)->handle(new Request(['query' => 'test', 'max_results' => $input]));
})->with([
    'too low' => [-5, 1],
    'zero' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [10, 10],
    'too high' => [15, 10],
]);
