<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackAiScrape;

it('has a description', function (): void {
    expect((new JigsawStackAiScrape)->description())->toContain('scraping');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackAiScrape))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackAiScrape)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('url')
        ->and($schema)->toHaveKey('element_prompts')
        ->and($schema)->toHaveKey('root_element_selector');
});

it('returns an error when the url is empty', function (): void {
    expect((new JigsawStackAiScrape)->handle(new Request(['url' => ' ', 'element_prompts' => 'price'])))
        ->toContain('URL is empty');
});

it('returns an error when the element prompts are empty', function (): void {
    expect((new JigsawStackAiScrape)->handle(new Request(['url' => 'https://x.test', 'element_prompts' => ' '])))
        ->toContain('element_prompts are empty');
});

it('returns an error when no valid element prompts remain', function (): void {
    expect((new JigsawStackAiScrape)->handle(new Request(['url' => 'https://x.test', 'element_prompts' => ',,'])))
        ->toContain('No valid element prompts');
});

it('splits element prompts and forwards them', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'data' => []])]);

    $result = (new JigsawStackAiScrape)->handle(new Request([
        'url' => 'https://x.test',
        'element_prompts' => 'product name, price',
        'root_element_selector' => '#content',
    ]));

    expect($result)->toContain('success');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/ai/scrape')
            ->and($request->data())->toMatchArray([
                'url' => 'https://x.test',
                'element_prompts' => ['product name', 'price'],
                'root_element_selector' => '#content',
            ]);

        return true;
    });
});

it('preserves a falsy "0" prompt token', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'data' => []])]);

    (new JigsawStackAiScrape)->handle(new Request([
        'url' => 'https://x.test',
        'element_prompts' => '0, price',
    ]));

    Http::assertSent(function ($request): true {
        expect($request->data())->toMatchArray(['element_prompts' => ['0', 'price']]);

        return true;
    });
});
