<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Brave\BraveSearch;

it('has a description', function (): void {
    expect((new BraveSearch)->description())->toContain('Brave Search');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new BraveSearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new BraveSearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('count')
        ->and($schema)->toHaveKey('offset')
        ->and($schema)->toHaveKey('freshness')
        ->and($schema)->toHaveKey('safesearch')
        ->and($schema)->toHaveKey('country')
        ->and($schema)->toHaveKey('search_lang')
        ->and($schema)->toHaveKey('extra_snippets');
});

it('returns an error when the query is empty', function (): void {
    $result = (new BraveSearch)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.brave.key');

    $result = (new BraveSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('not configured');
});

it('returns search results on success', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response([
            'query' => ['original' => 'Laravel news'],
            'web' => [
                'results' => [
                    [
                        'title' => 'Laravel 11 Released',
                        'url' => 'https://laravel.com',
                        'description' => 'Laravel 11 is here with new features.',
                    ],
                ],
            ],
        ]),
    ]);

    $result = (new BraveSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('Laravel 11 Released')
        ->and($result)->toContain('Laravel 11 is here');

    Http::assertSent(fn ($request): bool => $request->hasHeader('X-Subscription-Token', 'test-key')
        && $request->hasHeader('Accept', 'application/json')
        && $request->url() === 'https://api.search.brave.com/res/v1/web/search?q=Laravel%20news&count=20&offset=0&safesearch=moderate');
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response('Invalid API key', 401),
    ]);

    $result = (new BraveSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('failed with status 401');
});

it('returns an error when the response is not an array', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response('"ok"', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $result = (new BraveSearch)->handle(new Request(['query' => 'Laravel news']));

    expect($result)->toContain('response was invalid');
});

it('respects custom count and offset', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request([
        'query' => 'test',
        'count' => 5,
        'offset' => 2,
    ]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'count=5')
        && str_contains($request->url(), 'offset=2'));
});

it('respects custom safesearch freshness country and language', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request([
        'query' => 'test',
        'safesearch' => 'strict',
        'freshness' => 'pw',
        'country' => 'DE',
        'search_lang' => 'de',
        'extra_snippets' => true,
    ]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'safesearch=strict')
        && str_contains($request->url(), 'freshness=pw')
        && str_contains($request->url(), 'country=DE')
        && str_contains($request->url(), 'search_lang=de')
        && str_contains($request->url(), 'extra_snippets=1'));
});

it('accepts a custom freshness date range', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request([
        'query' => 'test',
        'freshness' => '2022-04-01to2022-07-30',
    ]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'freshness=2022-04-01to2022-07-30'));
});

it('uses defaults when optional params are omitted', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test']));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'count=20')
        && str_contains($request->url(), 'offset=0')
        && str_contains($request->url(), 'safesearch=moderate')
        && ! str_contains($request->url(), 'freshness=')
        && ! str_contains($request->url(), 'country=')
        && ! str_contains($request->url(), 'search_lang=')
        && ! str_contains($request->url(), 'extra_snippets='));
});

it('uses defaults when optional params are explicitly null', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request([
        'query' => 'test',
        'count' => null,
        'offset' => null,
        'freshness' => null,
        'safesearch' => null,
        'country' => null,
        'search_lang' => null,
        'extra_snippets' => null,
    ]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'count=20')
        && str_contains($request->url(), 'offset=0')
        && str_contains($request->url(), 'safesearch=moderate'));
});

it('uses configured defaults when optional params are omitted', function (): void {
    config()->set('services.brave.key', 'test-key');
    config()->set('ai.toolkit.brave.search.count', 8);
    config()->set('ai.toolkit.brave.search.offset', 1);
    config()->set('ai.toolkit.brave.search.safesearch', 'off');
    config()->set('ai.toolkit.brave.search.freshness', 'pm');
    config()->set('ai.toolkit.brave.search.country', 'US');
    config()->set('ai.toolkit.brave.search.search_lang', 'en');
    config()->set('ai.toolkit.brave.search.extra_snippets', true);

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test']));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'count=8')
        && str_contains($request->url(), 'offset=1')
        && str_contains($request->url(), 'safesearch=off')
        && str_contains($request->url(), 'freshness=pm')
        && str_contains($request->url(), 'country=US')
        && str_contains($request->url(), 'search_lang=en')
        && str_contains($request->url(), 'extra_snippets=1'));
});

it('falls back to moderate for invalid safesearch values', function (string $input): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test', 'safesearch' => $input]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'safesearch=moderate'));
})->with([
    'empty' => [''],
    'random' => ['foobar'],
    'wrong case' => ['Strict'],
]);

it('omits invalid freshness values', function (string $input): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test', 'freshness' => $input]));

    Http::assertSent(fn ($request): bool => ! str_contains($request->url(), 'freshness='));
})->with([
    'empty' => [''],
    'random' => ['last-week'],
    'wrong case' => ['PW'],
]);

it('falls back when configured defaults are invalid', function (): void {
    config()->set('services.brave.key', 'test-key');
    config()->set('ai.toolkit.brave.search.count', 'not-a-number');
    config()->set('ai.toolkit.brave.search.offset', 'nope');
    config()->set('ai.toolkit.brave.search.safesearch', 'Strict');
    config()->set('ai.toolkit.brave.search.freshness', 'last-week');
    config()->set('ai.toolkit.brave.search.country', '');
    config()->set('ai.toolkit.brave.search.search_lang', '');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test']));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'count=20')
        && str_contains($request->url(), 'offset=0')
        && str_contains($request->url(), 'safesearch=moderate')
        && ! str_contains($request->url(), 'freshness=')
        && ! str_contains($request->url(), 'country=')
        && ! str_contains($request->url(), 'search_lang='));
});

it('clamps count between 1 and 20', function (int $input, int $expected): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test', 'count' => $input]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'count='.$expected));
})->with([
    'too low' => [-5, 1],
    'zero' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [20, 20],
    'too high' => [50, 20],
]);

it('clamps offset between 0 and 9', function (int $input, int $expected): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake([
        'api.search.brave.com/*' => Http::response(['web' => ['results' => []]]),
    ]);

    (new BraveSearch)->handle(new Request(['query' => 'test', 'offset' => $input]));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'offset='.$expected));
})->with([
    'too low' => [-3, 0],
    'minimum' => [0, 0],
    'maximum' => [9, 9],
    'too high' => [12, 9],
]);

it('returns a friendly error when the request throws', function (): void {
    config()->set('services.brave.key', 'test-key');

    Http::fake(function (): void {
        throw new RuntimeException('Connection timed out');
    });

    $result = (new BraveSearch)->handle(new Request(['query' => 'test']));

    expect($result)->toContain('request failed')
        ->and($result)->toContain('Connection timed out');
});
