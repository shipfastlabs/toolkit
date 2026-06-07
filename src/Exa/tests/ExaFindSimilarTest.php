<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\ExaFindSimilar;

it('has a description', function (): void {
    expect((new ExaFindSimilar)->description())->toContain('similar');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new ExaFindSimilar))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new ExaFindSimilar)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('num_results')
        ->and($schema)->toHaveKey('include_domains')
        ->and($schema)->toHaveKey('exclude_domains')
        ->and($schema)->toHaveKey('include_text');
});

it('returns an error when the url is empty', function (): void {
    $result = (new ExaFindSimilar)->handle(new Request(['url' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.exa.key');

    $result = (new ExaFindSimilar)->handle(new Request(['url' => 'https://laravel.com']));

    expect($result)->toContain('not configured');
});

it('returns similar results on success', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/findSimilar' => Http::response([
            'results' => [
                ['title' => 'Symfony', 'url' => 'https://symfony.com'],
            ],
        ]),
    ]);

    $result = (new ExaFindSimilar)->handle(new Request(['url' => 'https://laravel.com']));

    expect($result)->toContain('Symfony');
});

it('sends the url and defaults', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('url', 'https://laravel.com')
            ->toHaveKey('numResults', 10)
            ->toHaveKey('contents', ['text' => true]);

        return Http::response(['results' => []]);
    });

    (new ExaFindSimilar)->handle(new Request(['url' => 'https://laravel.com']));
});

it('parses comma-separated include and exclude domains', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('includeDomains', ['arxiv.org', 'nature.com'])
            ->toHaveKey('excludeDomains', ['reddit.com']);

        return Http::response(['results' => []]);
    });

    (new ExaFindSimilar)->handle(new Request([
        'url' => 'https://laravel.com',
        'include_domains' => 'arxiv.org, nature.com',
        'exclude_domains' => 'reddit.com',
    ]));
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/findSimilar' => Http::response('Bad request', 400),
    ]);

    $result = (new ExaFindSimilar)->handle(new Request(['url' => 'https://laravel.com']));

    expect($result)->toContain('failed with status 400');
});

it('clamps num_results between 1 and 25', function (int $input, int $expected): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) use ($expected) {
        expect($request->data())->toHaveKey('numResults', $expected);

        return Http::response(['results' => []]);
    });

    (new ExaFindSimilar)->handle(new Request(['url' => 'https://laravel.com', 'num_results' => $input]));
})->with([
    'too low' => [0, 1],
    'too high' => [100, 25],
]);
