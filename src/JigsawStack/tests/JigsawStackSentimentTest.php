<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSentiment;

it('has a description', function (): void {
    expect((new JigsawStackSentiment)->description())->toContain('emotional tone');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackSentiment))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackSentiment)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text');
});

it('returns an error when the text is empty', function (): void {
    $result = (new JigsawStackSentiment)->handle(new Request(['text' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.jigsawstack.key');

    $result = (new JigsawStackSentiment)->handle(new Request(['text' => 'I love this']));

    expect($result)->toContain('not configured');
});

it('sends the api key as the x-api-key header', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'sentiment' => ['sentiment' => 'positive']])]);

    (new JigsawStackSentiment)->handle(new Request(['text' => 'I love this']));

    Http::assertSent(function ($request): true {
        expect($request->hasHeader('x-api-key', 'test-key'))->toBeTrue();

        return true;
    });
});

it('returns sentiment results on success', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake([
        'https://api.jigsawstack.com/v1/ai/sentiment' => Http::response([
            'success' => true,
            'sentiment' => ['sentiment' => 'positive', 'emotion' => 'joy', 'score' => 0.9],
        ]),
    ]);

    $result = (new JigsawStackSentiment)->handle(new Request(['text' => 'I love this']));

    expect($result)->toContain('positive')->and($result)->toContain('joy');
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake([
        'https://api.jigsawstack.com/v1/ai/sentiment' => Http::response('Invalid API key', 401),
    ]);

    $result = (new JigsawStackSentiment)->handle(new Request(['text' => 'I love this']));

    expect($result)->toContain('failed with status 401');
});

it('returns an error when the request throws', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    $result = (new JigsawStackSentiment)->handle(new Request(['text' => 'I love this']));

    expect($result)->toContain('The JigsawStack request failed')
        ->and($result)->toContain('Connection timed out');
});

it('returns an error when the response is not valid json', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake([
        'https://api.jigsawstack.com/v1/ai/sentiment' => Http::response('not json', 200),
    ]);

    $result = (new JigsawStackSentiment)->handle(new Request(['text' => 'I love this']));

    expect($result)->toContain('response was invalid');
});
