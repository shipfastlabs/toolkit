<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSpellCheck;

it('has a description', function (): void {
    expect((new JigsawStackSpellCheck)->description())->toContain('spelling');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackSpellCheck))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackSpellCheck)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text')->and($schema)->toHaveKey('language_code');
});

it('returns an error when the text is empty', function (): void {
    expect((new JigsawStackSpellCheck)->handle(new Request(['text' => ' '])))->toContain('empty');
});

it('checks spelling with an optional language code', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'auto_correct_text' => 'hello world'])]);

    $result = (new JigsawStackSpellCheck)->handle(new Request([
        'text' => 'helllo wrld',
        'language_code' => 'en',
    ]));

    expect($result)->toContain('hello world');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/validate/spell_check')
            ->and($request->data())->toMatchArray([
                'text' => 'helllo wrld',
                'language_code' => 'en',
            ]);

        return true;
    });
});
