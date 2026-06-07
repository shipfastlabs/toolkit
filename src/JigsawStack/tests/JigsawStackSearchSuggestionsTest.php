<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSearchSuggestions;

it('has a description', function (): void {
    expect((new JigsawStackSearchSuggestions)->description())->toContain('autocomplete');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackSearchSuggestions))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackSearchSuggestions)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query');
});

it('returns an error when the query is empty', function (): void {
    expect((new JigsawStackSearchSuggestions)->handle(new Request(['query' => ' '])))->toContain('empty');
});

it('sends a GET request with the query string', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'suggestions' => ['laravel', 'laravel news']])]);

    $result = (new JigsawStackSearchSuggestions)->handle(new Request(['query' => 'lara']));

    expect($result)->toContain('laravel news');

    Http::assertSent(function ($request): true {
        expect($request->method())->toBe('GET')
            ->and($request->url())->toBe('https://api.jigsawstack.com/v1/web/search/suggest?query=lara');

        return true;
    });
});
