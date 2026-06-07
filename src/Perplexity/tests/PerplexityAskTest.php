<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Perplexity\PerplexityAsk;

it('has a description', function (): void {
    expect((new PerplexityAsk)->description())->toContain('cited answer');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new PerplexityAsk))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new PerplexityAsk)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('model')
        ->and($schema)->toHaveKey('search_mode');
});

it('returns an error when the question is empty', function (): void {
    $result = (new PerplexityAsk)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.perplexity.key');

    $result = (new PerplexityAsk)->handle(new Request(['query' => 'Who created Laravel?']));

    expect($result)->toContain('not configured');
});

it('returns an answer on success', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake([
        'https://api.perplexity.ai/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'Laravel was created by Taylor Otwell.']],
            ],
            'citations' => ['https://laravel.com'],
        ]),
    ]);

    $result = (new PerplexityAsk)->handle(new Request(['query' => 'Who created Laravel?']));

    expect($result)->toContain('Taylor Otwell')
        ->and($result)->toContain('https://laravel.com');
});

it('sends the question as a user message', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data()['messages'])->toBe([
            ['role' => 'user', 'content' => 'Who created Laravel?'],
        ]);

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'Who created Laravel?']));
});

it('sends the api key as a bearer token', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->hasHeader('Authorization', 'Bearer test-key'))->toBeTrue();

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test']));
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake([
        'https://api.perplexity.ai/chat/completions' => Http::response('Invalid API key', 401),
    ]);

    $result = (new PerplexityAsk)->handle(new Request(['query' => 'test']));

    expect($result)->toContain('failed with status 401');
});

it('returns an error when the response is not an array', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake([
        'https://api.perplexity.ai/chat/completions' => Http::response('"plain string"', 200, ['Content-Type' => 'application/json']),
    ]);

    $result = (new PerplexityAsk)->handle(new Request(['query' => 'test']));

    expect($result)->toContain('invalid');
});

it('respects a custom model', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('model', 'sonar-pro');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test', 'model' => 'sonar-pro']));
});

it('respects a custom search_mode', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search_mode', 'academic');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test', 'search_mode' => 'academic']));
});

it('uses defaults when optional params are omitted', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('model', 'sonar')
            ->toHaveKey('search_mode', 'web');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test']));
});

it('uses defaults when optional params are explicitly null', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('model', 'sonar')
            ->toHaveKey('search_mode', 'web');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request([
        'query' => 'test',
        'model' => null,
        'search_mode' => null,
    ]));
});

it('reads the default model and search_mode from config', function (): void {
    config()->set('services.perplexity.key', 'test-key');
    config()->set('ai.toolkit.perplexity.ask.model', 'sonar-reasoning');
    config()->set('ai.toolkit.perplexity.ask.search_mode', 'academic');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('model', 'sonar-reasoning')
            ->toHaveKey('search_mode', 'academic');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test']));
});

it('falls back to sonar when the configured model is invalid', function (): void {
    config()->set('services.perplexity.key', 'test-key');
    config()->set('ai.toolkit.perplexity.ask.model', 12345);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('model', 'sonar');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test']));
});

it('falls back to web when the configured search_mode is invalid', function (): void {
    config()->set('services.perplexity.key', 'test-key');
    config()->set('ai.toolkit.perplexity.ask.search_mode', ['not', 'a', 'string']);

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('search_mode', 'web');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request(['query' => 'test']));
});

it('falls back to defaults for invalid model and search_mode values', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('model', 'sonar')
            ->toHaveKey('search_mode', 'web');

        return Http::response(['choices' => []]);
    });

    (new PerplexityAsk)->handle(new Request([
        'query' => 'test',
        'model' => 'gpt-4',
        'search_mode' => 'telepathy',
    ]));
});

it('returns a friendly error when the request throws', function (): void {
    config()->set('services.perplexity.key', 'test-key');

    Http::fake(function (): void {
        throw new RuntimeException('Connection timed out');
    });

    $result = (new PerplexityAsk)->handle(new Request(['query' => 'test']));

    expect($result)->toContain('request failed')
        ->and($result)->toContain('Connection timed out');
});
