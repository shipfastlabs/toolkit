<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackObjectDetection;

it('has a description', function (): void {
    expect((new JigsawStackObjectDetection)->description())->toContain('Detect');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackObjectDetection))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackObjectDetection)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('prompts')
        ->and($schema)->toHaveKey('annotated_image');
});

it('returns an error when the url is empty', function (): void {
    expect((new JigsawStackObjectDetection)->handle(new Request(['url' => ' '])))->toContain('empty');
});

it('detects all objects when no prompts are given', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'objects' => []])]);

    (new JigsawStackObjectDetection)->handle(new Request(['url' => 'https://x.test/a.png']));

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/object_detection')
            ->and($request->data())->toBe(['url' => 'https://x.test/a.png']);

        return true;
    });
});

it('splits prompts and forwards optional flags', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'objects' => [['label' => 'cat']]])]);

    $result = (new JigsawStackObjectDetection)->handle(new Request([
        'url' => 'https://x.test/a.png',
        'prompts' => 'cat, dog',
        'annotated_image' => true,
    ]));

    expect($result)->toContain('cat');

    Http::assertSent(function ($request): true {
        expect($request->data())->toMatchArray([
            'url' => 'https://x.test/a.png',
            'prompts' => ['cat', 'dog'],
            'annotated_image' => true,
        ]);

        return true;
    });
});

it('omits prompts when the list collapses to empty', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'objects' => []])]);

    (new JigsawStackObjectDetection)->handle(new Request([
        'url' => 'https://x.test/a.png',
        'prompts' => ' , ',
    ]));

    Http::assertSent(function ($request): true {
        expect($request->data())->toBe(['url' => 'https://x.test/a.png']);

        return true;
    });
});
