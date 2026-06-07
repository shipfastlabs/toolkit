<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackEmbedding;

it('has a description', function (): void {
    expect((new JigsawStackEmbedding)->description())->toContain('embeddings');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackEmbedding))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackEmbedding)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text')->and($schema)->toHaveKey('type');
});

it('returns an error when the text is empty', function (): void {
    expect((new JigsawStackEmbedding)->handle(new Request(['text' => ''])))->toContain('empty');
});

it('defaults the type to text', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'embeddings' => [[0.1, 0.2]]])]);

    (new JigsawStackEmbedding)->handle(new Request(['text' => 'hello world']));

    Http::assertSent(function ($request): true {
        expect($request->data())->toBe(['type' => 'text', 'text' => 'hello world']);

        return true;
    });
});

it('accepts a custom type', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'embeddings' => []])]);

    (new JigsawStackEmbedding)->handle(new Request(['text' => 'hello', 'type' => 'text-other']));

    Http::assertSent(function ($request): true {
        expect($request->data())->toHaveKey('type', 'text-other');

        return true;
    });
});
