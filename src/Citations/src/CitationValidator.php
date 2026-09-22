<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Citations;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[Strict]
class CitationValidator implements Tool
{
    public function description(): string
    {
        return <<<'TXT'
            Validate citation tokens in Markdown against a declared citation list
            and an allow-list of available sources. Returns coverage, unknown or
            undeclared tokens, duplicates, and shape warnings as JSON.
            TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'body_markdown' => $schema
                ->string()
                ->description('Markdown body that may contain citation tokens like [type:id]')
                ->required(),
            'citations' => $schema
                ->array()
                ->description('Declared citations. Each item should include type, id, and optional role.')
                ->required(),
            'sources' => $schema
                ->array()
                ->description('Available sources. Each item should include entity_type (or type) and entity_id (or id).')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $bodyMarkdown = (string) $request->string('body_markdown');
        $citations = $request->array('citations');
        $sources = $request->array('sources');

        $entityTypes = $this->allowedEntityTypes();
        $roles = $this->allowedRoles();
        $pattern = $this->tokenPattern($entityTypes);

        preg_match_all($pattern, $bodyMarkdown, $matches, PREG_SET_ORDER);

        $referenced = [];

        foreach ($matches as $match) {
            if (! isset($match[1], $match[2])) {
                continue;
            }

            $type = $this->asString($match[1]);
            $id = $this->asString($match[2]);

            if ($type === '') {
                continue;
            }

            if ($id === '') {
                continue;
            }

            $referenced[] = $this->formatToken($type, $id);
        }

        $referenced = array_values(array_unique($referenced));
        $available = [];

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            $type = $this->asString($source['entity_type'] ?? $source['type'] ?? null);
            $id = $this->asString($source['entity_id'] ?? $source['id'] ?? null);

            if ($type === '') {
                continue;
            }

            if ($id === '') {
                continue;
            }

            $available[] = $this->formatToken($type, $id);
        }

        $available = array_values(array_unique($available));
        $declared = [];
        $duplicates = [];
        $warnings = [];

        foreach ($citations as $citation) {
            if (! is_array($citation)) {
                $warnings[] = 'citation_not_object';

                continue;
            }

            $type = $this->asString($citation['type'] ?? null);
            $id = trim($this->asString($citation['id'] ?? null));
            $role = $this->asString($citation['role'] ?? null);

            if (! in_array($type, $entityTypes, true) || $id === '') {
                $warnings[] = 'citation_shape_invalid';

                continue;
            }

            if ($roles !== [] && ! in_array($role, $roles, true)) {
                $warnings[] = 'citation_role_invalid';
            }

            $token = $this->formatToken($type, $id);

            if (in_array($token, $declared, true)) {
                $duplicates[] = $token;
            }

            $declared[] = $token;
        }

        $unknownReferenced = array_values(array_diff($referenced, $available));
        $undeclaredReferenced = array_values(array_diff($referenced, $declared));
        $coverage = $referenced === []
            ? ($declared === [] ? 1.0 : 0.0)
            : count(array_intersect($referenced, $available)) / count($referenced);

        $payload = [
            'valid' => $unknownReferenced === []
                && $undeclaredReferenced === []
                && $duplicates === []
                && $warnings === [],
            'coverage' => $coverage,
            'referenced_tokens' => $referenced,
            'declared_tokens' => array_values(array_unique($declared)),
            'unknown_referenced_tokens' => $unknownReferenced,
            'undeclared_referenced_tokens' => $undeclaredReferenced,
            'duplicate_declared_tokens' => array_values(array_unique($duplicates)),
            'warnings' => array_values(array_unique($warnings)),
        ];

        return json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return list<string>
     */
    private function allowedEntityTypes(): array
    {
        $types = config('ai.toolkit.citations.entity_types', ['tag', 'category', 'performer']);

        if (! is_array($types)) {
            return ['tag', 'category', 'performer'];
        }

        $normalized = [];

        foreach ($types as $type) {
            $value = $this->asString($type);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized === [] ? ['tag', 'category', 'performer'] : $normalized;
    }

    /**
     * @return list<string>
     */
    private function allowedRoles(): array
    {
        $roles = config('ai.toolkit.citations.roles', ['primary', 'supporting', 'mention']);

        if (! is_array($roles)) {
            return ['primary', 'supporting', 'mention'];
        }

        $normalized = [];

        foreach ($roles as $role) {
            $value = $this->asString($role);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $entityTypes
     */
    private function tokenPattern(array $entityTypes): string
    {
        $configured = config('ai.toolkit.citations.token_pattern');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $types = implode('|', array_map(
            fn (string $type): string => preg_quote($type, '/'),
            $entityTypes,
        ));

        return '/\[('.$types.'):([1-9][0-9]*)\]/';
    }

    private function formatToken(string $type, string $id): string
    {
        return sprintf('%s:%s', $type, $id);
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
