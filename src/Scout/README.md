# shipfastlabs/toolkit-scout

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-scout.svg)](https://packagist.org/packages/shipfastlabs/toolkit-scout)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-scout.svg)](https://packagist.org/packages/shipfastlabs/toolkit-scout)

> Laravel Scout search tools for the Laravel AI SDK

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->


## Installation

```bash
composer require shipfastlabs/toolkit-scout
```

This package depends on `laravel/scout`. Configure Scout as usual in your application.

## Usage

Register every Scout tool at once with the `Scout` helper:

```php
use Shipfastlabs\Toolkit\Scout\Scout;

$tools = Scout::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Scout\ScoutSearch;

$tools = [
    new ScoutSearch,
];
```

## Tools

### ScoutSearch

Search allow-listed Eloquent models with Laravel Scout.

| Parameter | Type    | Required | Description                                                         |
|-----------|---------|----------|---------------------------------------------------------------------|
| `model`   | string  | yes      | Configured model alias under `ai.toolkit.scout.models`.             |
| `query`   | string  | yes      | The Scout search query.                                             |
| `limit`   | integer | no       | Maximum number of results to return (1-25). Defaults to 5.          |

## Configuration

Tools do not ship config files. Add model allow-lists and defaults to `config/ai.php`:

```php
'toolkit' => [
    'scout' => [
        'search' => [
            'limit' => (int) env('SCOUT_TOOL_LIMIT', 5),
        ],
        'models' => [
            // Short form:
            'posts' => App\Models\Post::class,

            // Or with display columns:
            'posts' => [
                'class' => App\Models\Post::class,
                'columns' => ['name', 'slug'],
            ],
        ],
    ],
],
```

| Config key | Default | Description |
|---|---|---|
| `ai.toolkit.scout.models` | `[]` | Allow-listed model aliases and optional display columns. |
| `ai.toolkit.scout.search.limit` | `5` | Default result limit (1-25). |

## Safety

- Only allow-listed model aliases can be searched.
- Result counts are clamped to 1-25.
- Non-searchable or missing model classes return friendly error strings.
- Scout exceptions are caught and returned as strings so the model can recover.

## Note for maintainers

This package intentionally depends on `laravel/scout` in addition to `laravel/ai`. Please confirm that extra dependency is acceptable for the catalog before releasing.
