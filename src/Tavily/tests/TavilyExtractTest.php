<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Tavily\TavilyExtract;

it('has a description', function (): void {
    expect((new TavilyExtract)->description())->toContain('Extract clean, structured content');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new TavilyExtract))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new TavilyExtract)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('urls')
        ->and($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('extract_depth')
        ->and($schema)->toHaveKey('format')
        ->and($schema)->toHaveKey('include_images');
});

it('returns an error when urls are empty', function (): void {
    $result = (new TavilyExtract)->handle(new Request(['urls' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.tavily.key');

    $result = (new TavilyExtract)->handle(new Request(['urls' => 'https://example.com']));

    expect($result)->toContain('not configured');
});

it('returns extracted content on success', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake([
        'https://api.tavily.com/extract' => Http::response([
            'results' => [
                [
                    'url' => 'https://example.com',
                    'raw_content' => 'Example content',
                ],
            ],
        ]),
    ]);

    $result = (new TavilyExtract)->handle(new Request(['urls' => 'https://example.com']));

    expect($result)->toContain('Example content');
});

it('rejects more than 20 urls', function (): void {
    $urls = implode(',', array_fill(0, 21, 'https://example.com'));

    $result = (new TavilyExtract)->handle(new Request(['urls' => $urls]));

    expect($result)->toContain('maximum of 20');
});

it('uses bearer token for authentication', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->header('Authorization'))->toContain('Bearer test-key');

        return Http::response([
            'results' => [
                [
                    'url' => 'https://example.com',
                    'raw_content' => 'Content',
                ],
            ],
        ]);
    });

    (new TavilyExtract)->handle(new Request(['urls' => 'https://example.com']));
});

it('respects custom extract_depth and format', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('extract_depth', 'advanced')
            ->toHaveKey('format', 'text');

        return Http::response([
            'results' => [
                [
                    'url' => 'https://example.com',
                    'raw_content' => 'Content',
                ],
            ],
        ]);
    });

    (new TavilyExtract)->handle(new Request([
        'urls' => 'https://example.com',
        'extract_depth' => 'advanced',
        'format' => 'text',
    ]));
});

it('falls back to defaults for invalid extract_depth and format', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('extract_depth', 'basic')
            ->toHaveKey('format', 'markdown');

        return Http::response([
            'results' => [
                [
                    'url' => 'https://example.com',
                    'raw_content' => 'Content',
                ],
            ],
        ]);
    });

    (new TavilyExtract)->handle(new Request([
        'urls' => 'https://example.com',
        'extract_depth' => 'invalid',
        'format' => 'html',
    ]));
});

it('handles multiple comma-separated urls', function (): void {
    config()->set('services.tavily.key', 'test-key');

    Http::fake(function ($request) {
        $urls = $request->data()['urls'];

        expect($urls)->toBeArray()->toHaveCount(2);

        return Http::response([
            'results' => [
                ['url' => 'https://a.com', 'raw_content' => 'A'],
                ['url' => 'https://b.com', 'raw_content' => 'B'],
            ],
        ]);
    });

    (new TavilyExtract)->handle(new Request(['urls' => 'https://a.com, https://b.com']));
});
