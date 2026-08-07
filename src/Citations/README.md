# shipfastlabs/toolkit-citations

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-citations.svg)](https://packagist.org/packages/shipfastlabs/toolkit-citations)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-citations.svg)](https://packagist.org/packages/shipfastlabs/toolkit-citations)

> Citation validation tools for the Laravel AI SDK

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->


## Installation

```bash
composer require shipfastlabs/toolkit-citations
```

## Usage

Register every Citations tool at once with the `Citations` helper:

```php
use Shipfastlabs\Toolkit\Citations\Citations;

$tools = Citations::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Citations\CitationValidator;

$tools = [
    new CitationValidator,
];
```

## Tools

### CitationValidator

Validate citation tokens in Markdown against a declared citation list and an allow-list of available sources.

| Parameter       | Type   | Required | Description                                                                 |
|-----------------|--------|----------|-----------------------------------------------------------------------------|
| `body_markdown` | string | yes      | Markdown body that may contain citation tokens like `[type:id]`.            |
| `citations`     | array  | yes      | Declared citations. Each item should include `type`, `id`, and optional `role`. |
| `sources`       | array  | yes      | Available sources. Each item should include `entity_type`/`type` and `entity_id`/`id`. |

Returns JSON with `valid`, `coverage`, referenced/declared/unknown/undeclared/duplicate tokens, and `warnings`.

## Configuration

Tools do not ship config files. Add optional defaults to `config/ai.php`:

```php
'toolkit' => [
    'citations' => [
        'entity_types' => ['tag', 'category', 'performer'],
        'roles' => ['primary', 'supporting', 'mention'],
        // Optional full regex override. Defaults to /[(types):(id)]/.
        // 'token_pattern' => '/\[(tag|category|performer):([1-9][0-9]*)\]/',
    ],
],
```

| Config key | Default | Description |
|---|---|---|
| `ai.toolkit.citations.entity_types` | `tag`, `category`, `performer` | Allowed citation entity types. |
| `ai.toolkit.citations.roles` | `primary`, `supporting`, `mention` | Allowed citation roles. Empty disables role validation. |
| `ai.toolkit.citations.token_pattern` | derived from entity types | Optional full regex override for body token extraction. |

## Safety

- Invalid citation shapes are reported as warnings instead of throwing.
- Unknown, undeclared, and duplicate tokens are returned for the model to correct.
- Entity types and roles are constrained by configuration allow-lists.
