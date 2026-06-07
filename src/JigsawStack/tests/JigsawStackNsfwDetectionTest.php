<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackNsfwDetection;

it('has a description', function (): void {
    expect((new JigsawStackNsfwDetection)->description())->toContain('NSFW');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackNsfwDetection))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackNsfwDetection)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url');
});

it('returns an error when the url is empty', function (): void {
    expect((new JigsawStackNsfwDetection)->handle(new Request(['url' => ' '])))->toContain('empty');
});

it('analyzes an image on success', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'nsfw' => false, 'nsfw_score' => 0.01])]);

    $result = (new JigsawStackNsfwDetection)->handle(new Request(['url' => 'https://x.test/a.png']));

    expect($result)->toContain('nsfw_score');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/validate/nsfw')
            ->and($request->data())->toBe(['url' => 'https://x.test/a.png']);

        return true;
    });
});
