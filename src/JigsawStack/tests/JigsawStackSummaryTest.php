<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSummary;

it('has a description', function (): void {
    expect((new JigsawStackSummary)->description())->toContain('Summarize');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackSummary))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackSummary)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text')
        ->and($schema)->toHaveKey('type')
        ->and($schema)->toHaveKey('max_points');
});

it('returns an error when the text is empty', function (): void {
    expect((new JigsawStackSummary)->handle(new Request(['text' => ' '])))->toContain('empty');
});

it('forwards optional parameters when provided', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'summary' => ['point one']])]);

    $result = (new JigsawStackSummary)->handle(new Request([
        'text' => 'A long article.',
        'type' => 'points',
        'max_points' => 5,
    ]));

    expect($result)->toContain('point one');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/summary')
            ->and($request->data())->toMatchArray([
                'text' => 'A long article.',
                'type' => 'points',
                'max_points' => 5,
            ]);

        return true;
    });
});

it('omits optional parameters when null', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'summary' => 'A summary.'])]);

    (new JigsawStackSummary)->handle(new Request([
        'text' => 'A long article.',
        'type' => null,
        'max_points' => null,
    ]));

    Http::assertSent(function ($request): true {
        expect($request->data())->toBe(['text' => 'A long article.']);

        return true;
    });
});
