<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackPrediction;

it('has a description', function (): void {
    expect((new JigsawStackPrediction)->description())->toContain('Forecast');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackPrediction))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackPrediction)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('dataset')->and($schema)->toHaveKey('steps');
});

it('returns an error when the dataset is empty', function (): void {
    expect((new JigsawStackPrediction)->handle(new Request(['dataset' => ' '])))->toContain('empty');
});

it('returns an error when the dataset is not valid json', function (): void {
    expect((new JigsawStackPrediction)->handle(new Request(['dataset' => '{not json'])))
        ->toContain('valid JSON');
});

it('returns an error when the dataset is not a json array', function (): void {
    expect((new JigsawStackPrediction)->handle(new Request(['dataset' => '42'])))
        ->toContain('JSON array');
});

it('forwards a decoded dataset and steps', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'prediction' => []])]);

    $result = (new JigsawStackPrediction)->handle(new Request([
        'dataset' => '[{"date":"2024-01-01","value":10}]',
        'steps' => 3,
    ]));

    expect($result)->toContain('success');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/prediction')
            ->and($request->data())->toMatchArray([
                'dataset' => [['date' => '2024-01-01', 'value' => 10]],
                'steps' => 3,
            ]);

        return true;
    });
});
