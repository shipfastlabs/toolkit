<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\ExaGetContents;

it('has a description', function (): void {
    expect((new ExaGetContents)->description())->toContain('contents');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new ExaGetContents))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new ExaGetContents)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('urls')
        ->and($schema)->toHaveKey('text')
        ->and($schema)->toHaveKey('summary')
        ->and($schema)->toHaveKey('highlights')
        ->and($schema)->toHaveKey('livecrawl');
});

it('returns an error when urls are empty', function (): void {
    $result = (new ExaGetContents)->handle(new Request(['urls' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.exa.key');

    $result = (new ExaGetContents)->handle(new Request(['urls' => 'https://laravel.com']));

    expect($result)->toContain('not configured');
});

it('rejects more than 20 urls', function (): void {
    config()->set('services.exa.key', 'test-key');

    $urls = implode(',', array_map(fn (int $i): string => 'https://example.com/'.$i, range(1, 21)));

    $result = (new ExaGetContents)->handle(new Request(['urls' => $urls]));

    expect($result)->toContain('maximum of 20');
});

it('returns contents on success', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/contents' => Http::response([
            'results' => [
                ['url' => 'https://laravel.com', 'text' => 'The PHP framework.'],
            ],
        ]),
    ]);

    $result = (new ExaGetContents)->handle(new Request(['urls' => 'https://laravel.com']));

    expect($result)->toContain('The PHP framework.');
});

it('sends urls as an array with defaults', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('urls', ['https://a.com', 'https://b.com'])
            ->toHaveKey('text', true)
            ->toHaveKey('livecrawl', 'fallback')
            ->not->toHaveKey('summary')
            ->not->toHaveKey('highlights');

        return Http::response(['results' => []]);
    });

    (new ExaGetContents)->handle(new Request(['urls' => 'https://a.com, https://b.com']));
});

it('sends text as false when explicitly disabled', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('text', false);

        return Http::response(['results' => []]);
    });

    (new ExaGetContents)->handle(new Request(['urls' => 'https://laravel.com', 'text' => false]));
});

it('includes summary and highlights when requested', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('summary', true)
            ->toHaveKey('highlights', true);

        return Http::response(['results' => []]);
    });

    (new ExaGetContents)->handle(new Request([
        'urls' => 'https://laravel.com',
        'summary' => true,
        'highlights' => true,
    ]));
});

it('falls back to fallback for invalid livecrawl values', function (string $input): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('livecrawl', 'fallback');

        return Http::response(['results' => []]);
    });

    (new ExaGetContents)->handle(new Request(['urls' => 'https://laravel.com', 'livecrawl' => $input]));
})->with([
    'empty' => [''],
    'random' => ['sometimes'],
]);

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/contents' => Http::response('Server error', 500),
    ]);

    $result = (new ExaGetContents)->handle(new Request(['urls' => 'https://laravel.com']));

    expect($result)->toContain('failed with status 500');
});
