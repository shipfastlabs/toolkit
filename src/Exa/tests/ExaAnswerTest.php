<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Exa\ExaAnswer;

it('has a description', function (): void {
    expect((new ExaAnswer)->description())->toContain('answer');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new ExaAnswer))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new ExaAnswer)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('include_text');
});

it('returns an error when the query is empty', function (): void {
    $result = (new ExaAnswer)->handle(new Request(['query' => '   ']));

    expect($result)->toContain('empty');
});

it('returns an error when no api key is configured', function (): void {
    config()->set('services.exa.key');

    $result = (new ExaAnswer)->handle(new Request(['query' => 'What is Laravel?']));

    expect($result)->toContain('not configured');
});

it('returns an answer on success', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/answer' => Http::response([
            'answer' => 'Laravel is a PHP web framework.',
            'citations' => [
                ['url' => 'https://laravel.com', 'title' => 'Laravel'],
            ],
        ]),
    ]);

    $result = (new ExaAnswer)->handle(new Request(['query' => 'What is Laravel?']));

    expect($result)->toContain('Laravel is a PHP web framework.')
        ->and($result)->toContain('https://laravel.com');
});

it('uses defaults when optional params are omitted', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())
            ->toHaveKey('query', 'What is Laravel?')
            ->not->toHaveKey('text');

        return Http::response(['answer' => 'ok']);
    });

    (new ExaAnswer)->handle(new Request(['query' => 'What is Laravel?']));
});

it('includes text when include_text is true', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake(function ($request) {
        expect($request->data())->toHaveKey('text', true);

        return Http::response(['answer' => 'ok']);
    });

    (new ExaAnswer)->handle(new Request(['query' => 'What is Laravel?', 'include_text' => true]));
});

it('returns an error when the api responds with a failure', function (): void {
    config()->set('services.exa.key', 'test-key');

    Http::fake([
        'https://api.exa.ai/answer' => Http::response('Rate limited', 429),
    ]);

    $result = (new ExaAnswer)->handle(new Request(['query' => 'What is Laravel?']));

    expect($result)->toContain('failed with status 429');
});
