<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackTranslateImage;

it('has a description', function (): void {
    expect((new JigsawStackTranslateImage)->description())->toContain('translate');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackTranslateImage))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackTranslateImage)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')->and($schema)->toHaveKey('target_language');
});

it('returns an error when the url is empty', function (): void {
    expect((new JigsawStackTranslateImage)->handle(new Request(['url' => ' ', 'target_language' => 'es'])))
        ->toContain('empty');
});

it('returns an error when the target language is empty', function (): void {
    expect((new JigsawStackTranslateImage)->handle(new Request(['url' => 'https://x.test/a.png', 'target_language' => ' '])))
        ->toContain('target_language');
});

it('translates an image on success', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'url' => 'https://x.test/translated.png'])]);

    $result = (new JigsawStackTranslateImage)->handle(new Request([
        'url' => 'https://x.test/a.png',
        'target_language' => 'es',
    ]));

    expect($result)->toContain('translated.png');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/translate/image')
            ->and($request->data())->toMatchArray([
                'url' => 'https://x.test/a.png',
                'target_language' => 'es',
                'return_type' => 'url',
            ]);

        return true;
    });
});
