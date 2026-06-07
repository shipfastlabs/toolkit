<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackTranslateText;

it('has a description', function (): void {
    expect((new JigsawStackTranslateText)->description())->toContain('Translate text');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackTranslateText))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackTranslateText)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text')
        ->and($schema)->toHaveKey('target_language')
        ->and($schema)->toHaveKey('current_language');
});

it('returns an error when the text is empty', function (): void {
    expect((new JigsawStackTranslateText)->handle(new Request(['text' => ' ', 'target_language' => 'es'])))
        ->toContain('empty');
});

it('returns an error when the target language is empty', function (): void {
    expect((new JigsawStackTranslateText)->handle(new Request(['text' => 'Hello', 'target_language' => ' '])))
        ->toContain('target_language');
});

it('translates text on success', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'translated_text' => 'Hola'])]);

    $result = (new JigsawStackTranslateText)->handle(new Request([
        'text' => 'Hello',
        'target_language' => 'es',
        'current_language' => 'en',
    ]));

    expect($result)->toContain('Hola');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/translate')
            ->and($request->data())->toMatchArray([
                'text' => 'Hello',
                'target_language' => 'es',
                'current_language' => 'en',
            ]);

        return true;
    });
});
