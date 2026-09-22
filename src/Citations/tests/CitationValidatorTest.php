<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Citations\CitationValidator;

it('has a description', function (): void {
    expect((new CitationValidator)->description())->toContain('Validate citation tokens');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new CitationValidator))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new CitationValidator)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('body_markdown')
        ->and($schema)->toHaveKey('citations')
        ->and($schema)->toHaveKey('sources');
});

it('validates a clean citation set', function (): void {
    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See [tag:1] and [category:2].',
        'citations' => [
            ['type' => 'tag', 'id' => '1', 'role' => 'primary'],
            ['type' => 'category', 'id' => '2', 'role' => 'supporting'],
        ],
        'sources' => [
            ['entity_type' => 'tag', 'entity_id' => 1],
            ['type' => 'category', 'id' => 2],
        ],
    ])), true);

    expect($result['valid'])->toBeTrue()
        ->and($result['coverage'])->toEqual(1.0)
        ->and($result['referenced_tokens'])->toEqualCanonicalizing(['tag:1', 'category:2'])
        ->and($result['warnings'])->toBeEmpty();
});

it('flags unknown undeclared duplicate and shape issues', function (): void {
    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See [tag:1] [tag:9] [tag:1].',
        'citations' => [
            ['type' => 'tag', 'id' => '1', 'role' => 'primary'],
            ['type' => 'tag', 'id' => '1', 'role' => 'supporting'],
            'not-an-object',
            ['type' => 'unknown', 'id' => '3', 'role' => 'primary'],
            ['type' => 'tag', 'id' => '', 'role' => 'primary'],
            ['type' => 'tag', 'id' => '4', 'role' => 'invalid-role'],
        ],
        'sources' => [
            ['entity_type' => 'tag', 'entity_id' => 1],
            'skip-me',
        ],
    ])), true);

    expect($result['valid'])->toBeFalse()
        ->and($result['unknown_referenced_tokens'])->toContain('tag:9')
        ->and($result['undeclared_referenced_tokens'])->toContain('tag:9')
        ->and($result['duplicate_declared_tokens'])->toContain('tag:1')
        ->and($result['warnings'])->toContain('citation_not_object')
        ->and($result['warnings'])->toContain('citation_shape_invalid')
        ->and($result['warnings'])->toContain('citation_role_invalid');
});

it('returns full coverage when nothing is referenced or declared', function (): void {
    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'No citations here.',
        'citations' => [],
        'sources' => [],
    ])), true);

    expect($result['valid'])->toBeTrue()
        ->and($result['coverage'])->toEqual(1.0);
});

it('returns zero coverage when citations are declared without references', function (): void {
    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'No citations here.',
        'citations' => [
            ['type' => 'tag', 'id' => '1', 'role' => 'primary'],
        ],
        'sources' => [
            ['entity_type' => 'tag', 'entity_id' => 1],
        ],
    ])), true);

    expect($result['coverage'])->toEqual(0.0);
});

it('uses configured entity types roles and token pattern', function (): void {
    config()->set('ai.toolkit.citations.entity_types', ['video']);
    config()->set('ai.toolkit.citations.roles', ['source']);
    config()->set('ai.toolkit.citations.token_pattern', '/\{(video):([1-9][0-9]*)\}/');

    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See {video:7} and [tag:1].',
        'citations' => [
            ['type' => 'video', 'id' => '7', 'role' => 'source'],
        ],
        'sources' => [
            ['entity_type' => 'video', 'entity_id' => 7],
        ],
    ])), true);

    expect($result['valid'])->toBeTrue()
        ->and($result['referenced_tokens'])->toBe(['video:7']);
});

it('falls back when configured entity types or roles are invalid', function (): void {
    config()->set('ai.toolkit.citations.entity_types', 'not-an-array');
    config()->set('ai.toolkit.citations.roles', 'not-an-array');
    config()->set('ai.toolkit.citations.token_pattern', '');

    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See [performer:3].',
        'citations' => [
            ['type' => 'performer', 'id' => '3', 'role' => 'mention'],
        ],
        'sources' => [
            ['entity_type' => 'performer', 'entity_id' => 3],
        ],
    ])), true);

    expect($result['valid'])->toBeTrue()
        ->and($result['referenced_tokens'])->toBe(['performer:3']);
});

it('filters empty configured entity types and roles', function (): void {
    config()->set('ai.toolkit.citations.entity_types', ['tag', '', null]);
    config()->set('ai.toolkit.citations.roles', ['primary', '']);

    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See [tag:1].',
        'citations' => [
            ['type' => 'tag', 'id' => '1', 'role' => 'primary'],
        ],
        'sources' => [
            ['entity_type' => 'tag', 'entity_id' => 1],
        ],
    ])), true);

    expect($result['valid'])->toBeTrue();
});

it('falls back when configured entity types are empty', function (): void {
    config()->set('ai.toolkit.citations.entity_types', []);
    config()->set('ai.toolkit.citations.roles', []);

    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See [tag:1].',
        'citations' => [
            ['type' => 'tag', 'id' => '1', 'role' => 'anything'],
        ],
        'sources' => [
            ['entity_type' => 'tag', 'entity_id' => 1],
        ],
    ])), true);

    expect($result['valid'])->toBeTrue()
        ->and($result['warnings'])->toBeEmpty();
});

it('ignores malformed token matches and empty source identities', function (): void {
    config()->set('ai.toolkit.citations.token_pattern', '/(?:\[([a-z]*):([0-9]*)\]|\[skip\])/');

    $result = json_decode((new CitationValidator)->handle(new Request([
        'body_markdown' => 'See [tag:] [:1] [tag:2] [skip].',
        'citations' => [
            ['type' => 'tag', 'id' => '2', 'role' => 'primary'],
        ],
        'sources' => [
            ['entity_type' => '', 'entity_id' => 1],
            ['entity_type' => 'tag', 'entity_id' => ''],
            ['entity_type' => 'tag', 'entity_id' => 2],
            ['entity_type' => true, 'entity_id' => false],
        ],
    ])), true);

    expect($result['referenced_tokens'])->toBe(['tag:2'])
        ->and($result['valid'])->toBeTrue();
});
