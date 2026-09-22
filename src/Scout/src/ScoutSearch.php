<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Scout;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

#[Strict]
class ScoutSearch implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Search allow-listed Eloquent models with Laravel Scout.
            Pass a configured model alias and a query; returns a bounded list of
            matching records with their configured display columns.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'model' => $schema
                ->string()
                ->description('Configured model alias to search (see ai.toolkit.scout.models)')
                ->required(),
            'query' => $schema
                ->string()
                ->description('The Scout search query')
                ->required(),
            'limit' => $schema
                ->integer()
                ->description('Maximum number of results to return (1-25, default: 5)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $modelAlias = trim((string) $request->string('model'));
        $query = $request->string('query')->trim();

        if ($modelAlias === '') {
            return 'The model alias is empty. Provide a configured model alias to search.';
        }

        if ($query->isEmpty()) {
            return 'The search query is empty. Provide a query to search for.';
        }

        $modelConfig = $this->resolveModelConfig($modelAlias);

        if ($modelConfig === null) {
            return sprintf(
                'The model alias [%s] is not allow-listed. Configure it under ai.toolkit.scout.models.',
                $modelAlias
            );
        }

        $class = $modelConfig['class'];

        if ($class === '' || ! class_exists($class)) {
            return sprintf('The model class for alias [%s] could not be resolved.', $modelAlias);
        }

        if (! is_subclass_of($class, Model::class)) {
            return sprintf('The model class for alias [%s] must extend Eloquent Model.', $modelAlias);
        }

        if (! method_exists($class, 'search')) {
            return sprintf('The model class for alias [%s] is not searchable with Laravel Scout.', $modelAlias);
        }

        $limit = $request->filled('limit')
            ? $request->integer('limit')
            : $this->defaultLimit();

        $limit = max(1, min(25, $limit));

        $columns = $modelConfig['columns'];

        try {
            $builder = $class::search((string) $query);

            if (! is_object($builder) || ! method_exists($builder, 'take')) {
                return sprintf('The Scout search builder for alias [%s] is invalid.', $modelAlias);
            }

            $limited = $builder->take($limit);

            if (! is_object($limited) || ! method_exists($limited, 'get')) {
                return sprintf('The Scout search builder for alias [%s] is invalid.', $modelAlias);
            }

            $results = $limited->get();
        } catch (Throwable $throwable) {
            return sprintf('The Scout search request failed: %s', $throwable->getMessage());
        }

        if (! $results instanceof Collection) {
            return sprintf('The Scout search response for alias [%s] was invalid.', $modelAlias);
        }

        $mapped = [];

        foreach ($results as $entity) {
            if (! $entity instanceof Model) {
                continue;
            }

            $row = [
                'model' => $modelAlias,
                'id' => $entity->getKey(),
            ];

            foreach ($columns as $column) {
                $row[$column] = $entity->getAttribute($column);
            }

            $mapped[] = $row;
        }

        $payload = [
            'model' => $modelAlias,
            'query' => (string) $query,
            'results' => $mapped,
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{class: string, columns: list<string>}|null
     */
    private function resolveModelConfig(string $alias): ?array
    {
        $models = config('ai.toolkit.scout.models', []);

        if (! is_array($models) || ! array_key_exists($alias, $models)) {
            return null;
        }

        $config = $models[$alias];

        if (is_string($config)) {
            return [
                'class' => $config,
                'columns' => ['name'],
            ];
        }

        if (! is_array($config) || ! array_key_exists('class', $config)) {
            return null;
        }

        $class = $config['class'];

        if (! is_string($class)) {
            return null;
        }

        $columns = $config['columns'] ?? ['name'];

        if (! is_array($columns)) {
            $columns = ['name'];
        }

        $normalizedColumns = [];

        foreach ($columns as $column) {
            $value = $this->asString($column);

            if ($value !== '') {
                $normalizedColumns[] = $value;
            }
        }

        if ($normalizedColumns === []) {
            $normalizedColumns = ['name'];
        }

        return [
            'class' => $class,
            'columns' => $normalizedColumns,
        ];
    }

    private function defaultLimit(): int
    {
        $limit = config('ai.toolkit.scout.search.limit', 5);

        return is_numeric($limit) ? (int) $limit : 5;
    }

    private function asString(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            default => '',
        };
    }
}
