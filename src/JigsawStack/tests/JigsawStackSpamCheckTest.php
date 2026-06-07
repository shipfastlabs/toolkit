<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSpamCheck;

it('has a description', function (): void {
    expect((new JigsawStackSpamCheck)->description())->toContain('spam');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new JigsawStackSpamCheck))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new JigsawStackSpamCheck)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('text');
});

it('returns an error when the text is empty', function (): void {
    expect((new JigsawStackSpamCheck)->handle(new Request(['text' => ' '])))->toContain('empty');
});

it('checks text for spam on success', function (): void {
    config()->set('services.jigsawstack.key', 'test-key');

    Http::fake(['*' => Http::response(['success' => true, 'check' => ['is_spam' => true, 'score' => 0.98]])]);

    $result = (new JigsawStackSpamCheck)->handle(new Request(['text' => 'Win a free prize now!']));

    expect($result)->toContain('is_spam');

    Http::assertSent(function ($request): true {
        expect($request->url())->toBe('https://api.jigsawstack.com/v1/validate/spam_check')
            ->and($request->data())->toBe(['text' => 'Win a free prize now!']);

        return true;
    });
});
