<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSpeechToText;

it('has a description', function (): void {
    expect((new JigsawStackSpeechToText)->description())->toContain('Transcribe');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackSpeechToText))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackSpeechToText)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('language')
        ->and($schema)->toHaveKey('translate');
});

it('returns an error when the url is empty', function (): void {
    expect((new JigsawStackSpeechToText)->handle(new Request(['url' => ' '])))->toContain('empty');
});

it('transcribes audio with optional parameters', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'text' => 'Bonjour'])]);

    $result = (new JigsawStackSpeechToText)->handle(new Request([
        'url' => 'https://x.test/a.mp3',
        'language' => 'fr',
        'translate' => true,
    ]));

    expect($result)->toContain('Bonjour');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/transcribe')
            ->and($request->data())->toMatchArray([
                'url' => 'https://x.test/a.mp3',
                'language' => 'fr',
                'translate' => true,
            ]);

        return true;
    });
});
