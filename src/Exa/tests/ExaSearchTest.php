<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\ExaSearch;

it('has a description', function (): void {
    expect((new ExaSearch)->description())->toContain('Search the web');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new ExaSearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new ExaSearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('type')
        ->and($schema)->toHaveKey('num_results')
        ->and($schema)->toHaveKey('category')
        ->and($schema)->toHaveKey('include_domains')
        ->and($schema)->toHaveKey('exclude_domains')
        ->and($schema)->toHaveKey('include_text');
});

it('returns an error when the query is empty', function (): void {
    $result = (new ExaSearch)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.exa.key');

    $result = (new ExaSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('not configured');
});

it('returns search results on success', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/search' => Http::response([
            'results' => [
                [
                    'title' => 'Laravel 12 Released',
                    'url' => 'https://laravel.com',
                    'text' => 'Laravel 12 is here with new features.',
                ],
            ],
        ]),
    ]);

    $result = (new ExaSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('Laravel 12 Released')
        ->and($result)->toContain('Laravel 12 is here');
});

it('sends the api key in the x-api-key header', function (): void {
    config()->set('services.exa.key', 'secret-key');

    Http::fake(function ($request) {
        expect($request->header('x-api-key'))->toContain('secret-key');

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request(['query' => 'test']));
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/search' => Http::response('Invalid API key', 401),
    ]);

    $result = (new ExaSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('failed with status 401');
});

it('uses defaults when optional params are omitted', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('type', 'auto')
            ->toHaveKey('numResults', 10)
            ->toHaveKey('contents', ['text' => true]);

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request(['query' => 'test']));
});

it('uses defaults when optional params are explicitly null', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('type', 'auto')
            ->toHaveKey('numResults', 10);

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request([
        'query' => 'test',
        'type' => null,
        'num_results' => null,
        'category' => null,
        'include_domains' => null,
        'exclude_domains' => null,
        'include_text' => null,
    ]));
});

it('omits contents when include_text is false', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->not->toHaveKey('contents');

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request(['query' => 'test', 'include_text' => false]));
});

it('parses comma-separated include and exclude domains', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('includeDomains', ['arxiv.org', 'nature.com'])
            ->toHaveKey('excludeDomains', ['reddit.com']);

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request([
        'query' => 'test',
        'include_domains' => 'arxiv.org, nature.com',
        'exclude_domains' => 'reddit.com',
    ]));
});

it('drops an invalid category', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->not->toHaveKey('category');

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request(['query' => 'test', 'category' => 'not-a-category']));
});

it('falls back to auto for invalid type values', function (string $input): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('type', 'auto');

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request(['query' => 'test', 'type' => $input]));
})->with([
    'empty' => [''],
    'random' => ['foobar'],
    'wrong case' => ['Neural'],
]);

it('clamps num_results between 1 and 25', function (int $input, int $expected): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('numResults', $expected);

        return Http::response(['results' => []]);
    });

    (new ExaSearch)->handle(new Request(['query' => 'test', 'num_results' => $input]));
})->with([
    'too low' => [-5, 1],
    'zero' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [25, 25],
    'too high' => [100, 25],
]);
