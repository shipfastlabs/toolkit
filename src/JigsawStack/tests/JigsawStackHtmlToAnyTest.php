<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackHtmlToAny;

it('has a description', function (): void {
    expect((new JigsawStackHtmlToAny)->description())->toContain('image or PDF');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackHtmlToAny))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackHtmlToAny)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('html')
        ->and($schema)->toHaveKey('type')
        ->and($schema)->toHaveKey('full_page');
});

it('returns an error when the html is empty', function (): void {
    expect((new JigsawStackHtmlToAny)->handle(new Request(['html' => ' '])))->toContain('empty');
});

it('renders html on success', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'url' => 'https://x.test/out.pdf'])]);

    $result = (new JigsawStackHtmlToAny)->handle(new Request([
        'html' => '<h1>Hi</h1>',
        'type' => 'pdf',
        'full_page' => true,
    ]));

    expect($result)->toContain('out.pdf');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/web/html_to_any')
            ->and($request->data())->toMatchArray([
                'html' => '<h1>Hi</h1>',
                'return_type' => 'url',
                'type' => 'pdf',
                'full_page' => true,
            ]);

        return true;
    });
});
