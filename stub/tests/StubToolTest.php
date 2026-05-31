<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Stub\StubTool;

it('has a description and is strict', function (): void {
    expect((new StubTool)->description())->not->toBeEmpty()
        ->and(Strict::isAppliedTo(new StubTool))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new StubTool)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('input');
});

it('handles a request', function (): void {
    $result = (new StubTool)->handle(new Request(['input' => 'hello']));

    expect($result)->toContain('hello');
});
