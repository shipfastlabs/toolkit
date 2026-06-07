<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackVocr;

it('has a description', function (): void {
    expect((new JigsawStackVocr)->description())->toContain('OCR');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackVocr))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackVocr)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')->and($schema)->toHaveKey('prompt');
});

it('returns an error when the url is empty', function (): void {
    expect((new JigsawStackVocr)->handle(new Request(['url' => ' '])))->toContain('empty');
});

it('reads an image with an optional prompt', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'context' => 'Total: $9.99'])]);

    $result = (new JigsawStackVocr)->handle(new Request([
        'url' => 'https://x.test/a.png',
        'prompt' => 'Read the receipt total',
    ]));

    expect($result)->toContain('9.99');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/vocr')
            ->and($request->data())->toMatchArray([
                'url' => 'https://x.test/a.png',
                'prompt' => 'Read the receipt total',
            ]);

        return true;
    });
});
