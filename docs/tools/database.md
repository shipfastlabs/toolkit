# Database

> Read-only database query tool for the Laravel AI SDK

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

## Installation

```bash
composer require shipfastlabs/toolkit-database
```

## Usage

Add the tool to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Database\DatabaseTool;

$tools = [new DatabaseTool];
```

## Input schema

| Parameter | Type | Required | Description |
|---|---|---|---|
| `query` | string | yes | A single read-only SQL `SELECT` statement. |

Matching rows are returned as pretty-printed JSON.

## Configuration

This tool ships no config file or service provider. It reads its settings from the `ai.toolkit.database` key of the
Laravel AI SDK's existing `config/ai.php`. Add the section manually:

```php
// config/ai.php

return [

    // ... existing laravel/ai config ...

    'toolkit' => [
        'database' => [
            'connection' => env('TOOLKIT_DATABASE_CONNECTION'),
            'max_rows' => (int) env('TOOLKIT_DATABASE_MAX_ROWS', 100),
        ],
    ],

];
```

| Key | Default | Description |
|---|---|---|
| `ai.toolkit.database.connection` | `null` (default connection) | The connection to query. Point at a read-only replica for extra safety. |
| `ai.toolkit.database.max_rows` | `100` | A `LIMIT` of this size is appended to any query that lacks one. |

## Safety

- **Read-only enforced**: only a single statement beginning with `SELECT` (or a `WITH … SELECT` CTE) is allowed.
- **Write keywords rejected**: `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER` and similar keywords are refused, even inside an otherwise-`SELECT` statement.
- **Single statement only**: queries containing `;` separators are refused.
- **Row cap**: results are bounded by `max_rows`.
- Query failures are returned to the model as strings, not thrown.
