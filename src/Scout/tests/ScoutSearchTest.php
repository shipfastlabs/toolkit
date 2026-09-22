<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Scout\ScoutSearch;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\BrokenSearchableModel;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\FakeSearchableModel;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\InvalidBuilderSearchableModel;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\MissingGetSearchableModel;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\MixedResultsSearchableModel;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\NonCollectionSearchableModel;
use Shipfastlabs\Toolkit\Scout\Tests\Fixtures\ThrowingSearchableModel;

it('has a description', function (): void {
    expect((new ScoutSearch)->description())->toContain('Laravel Scout');
});

it('is marked as strict', function (): void {
    expect(Strict::isAppliedTo(new ScoutSearch))->toBeTrue();
});

it('exposes its schema', function (): void {
    $schema = (new ScoutSearch)->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('model')
        ->and($schema)->toHaveKey('query')
        ->and($schema)->toHaveKey('limit');
});

it('returns an error when the model alias is empty', function (): void {
    $result = (new ScoutSearch)->handle(new Request([
        'model' => '   ',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('model alias is empty');
});

it('returns an error when the query is empty', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => FakeSearchableModel::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => '   ',
    ]));

    expect($result)->toContain('search query is empty');
});

it('returns an error when the model alias is not allow-listed', function (): void {
    config()->set('ai.toolkit.scout.models', []);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('not allow-listed');
});

it('returns an error when the model class cannot be resolved', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => 'App\\Missing\\Model',
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('could not be resolved');
});

it('returns an error when the model class is empty', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => [
            'class' => '',
            'columns' => ['name'],
        ],
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('could not be resolved');
});

it('returns an error when the model class is not a string', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => [
            'class' => 123,
            'columns' => ['name'],
        ],
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('not allow-listed');
});

it('returns an error when the configured class is not a model', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => stdClass::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('must extend Eloquent Model');
});

it('returns an error when the model is not searchable', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => BrokenSearchableModel::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('not searchable');
});

it('returns search results on success', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => [
            'class' => FakeSearchableModel::class,
            'columns' => ['name', 'slug'],
        ],
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
        'limit' => 2,
    ])), true);

    expect($result['model'])->toBe('posts')
        ->and($result['query'])->toBe('laravel')
        ->and($result['results'])->toHaveCount(2)
        ->and($result['results'][0])->toMatchArray([
            'model' => 'posts',
            'id' => 1,
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
});

it('accepts a string model class config and default columns', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => FakeSearchableModel::class,
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ])), true);

    expect($result['results'][0])->toHaveKey('name')
        ->and($result['results'][0])->not->toHaveKey('slug');
});

it('uses the configured default limit', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => FakeSearchableModel::class,
    ]);
    config()->set('ai.toolkit.scout.search.limit', 1);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ])), true);

    expect($result['results'])->toHaveCount(1);
});

it('clamps limit between 1 and 25', function (int $input, int $expected): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => FakeSearchableModel::class,
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
        'limit' => $input,
    ])), true);

    expect($result['results'])->toHaveCount(min($expected, 3));
})->with([
    'too low' => [-5, 1],
    'zero' => [0, 1],
    'minimum' => [1, 1],
    'maximum' => [25, 25],
    'too high' => [50, 25],
]);

it('falls back when configured limit is not numeric', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => FakeSearchableModel::class,
    ]);
    config()->set('ai.toolkit.scout.search.limit', 'nope');

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
        'limit' => null,
    ])), true);

    expect($result['results'])->toHaveCount(3);
});

it('returns an error when model config is malformed', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => ['columns' => ['name']],
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('not allow-listed');
});

it('falls back to name when columns are empty', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => [
            'class' => FakeSearchableModel::class,
            'columns' => [],
        ],
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
        'limit' => 1,
    ])), true);

    expect($result['results'][0])->toHaveKey('name');
});

it('returns a friendly error when the search throws', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => ThrowingSearchableModel::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('Scout search request failed')
        ->and($result)->toContain('boom');
});

it('ignores non-array models config', function (): void {
    config()->set('ai.toolkit.scout.models', 'invalid');

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('not allow-listed');
});

it('falls back to name when columns config is not an array', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => [
            'class' => FakeSearchableModel::class,
            'columns' => 'name',
        ],
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
        'limit' => 1,
    ])), true);

    expect($result['results'][0])->toHaveKey('name');
});

it('returns an error when the search builder is invalid', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => InvalidBuilderSearchableModel::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('search builder');
});

it('returns an error when the limited builder cannot get results', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => MissingGetSearchableModel::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('search builder');
});

it('returns an error when the search response is not a collection', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => NonCollectionSearchableModel::class,
    ]);

    $result = (new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ]));

    expect($result)->toContain('response');
});

it('skips non-model results from the search collection', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => MixedResultsSearchableModel::class,
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
    ])), true);

    expect($result['results'])->toHaveCount(1)
        ->and($result['results'][0]['name'])->toBe('Laravel');
});

it('stringifies numeric column names from config', function (): void {
    config()->set('ai.toolkit.scout.models', [
        'posts' => [
            'class' => FakeSearchableModel::class,
            'columns' => [1.5, 'name'],
        ],
    ]);

    $result = json_decode((new ScoutSearch)->handle(new Request([
        'model' => 'posts',
        'query' => 'laravel',
        'limit' => 1,
    ])), true);

    expect($result['results'][0])->toHaveKey('1.5')
        ->and($result['results'][0])->toHaveKey('name');
});
