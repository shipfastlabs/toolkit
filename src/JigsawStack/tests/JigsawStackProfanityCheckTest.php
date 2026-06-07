<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackProfanityCheck;

it('has a description', function (): void {
    expect((new JigsawStackProfanityCheck)->description())->toContain('profanity');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackProfanityCheck))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackProfanityCheck)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text')->and($schema)->toHaveKey('censor_replacement');
});

it('returns an error when the text is empty', function (): void {
    expect((new JigsawStackProfanityCheck)->handle(new Request(['text' => ' '])))->toContain('empty');
});

it('checks text with a custom censor replacement', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'profanities_found' => 0])]);

    $result = (new JigsawStackProfanityCheck)->handle(new Request([
        'text' => 'some text',
        'censor_replacement' => '#',
    ]));

    expect($result)->toContain('profanities_found');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/validate/profanity')
            ->and($request->data())->toMatchArray([
                'text' => 'some text',
                'censor_replacement' => '#',
            ]);

        return true;
    });
});
