# shipfastlabs/toolkit-brave

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-brave.svg)](https://packagist.org/packages/shipfastlabs/toolkit-brave)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-brave.svg)](https://packagist.org/packages/shipfastlabs/toolkit-brave)

> Brave Search tools for the Laravel AI SDK - Web Search

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->


## Installation

```bash
composer require shipfastlabs/toolkit-brave
```

## Usage

Register every Brave tool at once with the `Brave` helper:

```php
use Shipfastlabs\Toolkit\Brave\Brave;

$tools = Brave::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Brave\BraveSearch;

$tools = [
    new BraveSearch,
];
```

## Tools

### BraveSearch

Search the web for real-time information via the Brave Search API.

| Parameter         | Type    | Required | Description                                                                 |
|-------------------|---------|----------|-----------------------------------------------------------------------------|
| `query`           | string  | yes      | The search query to look up on the web.                                     |
| `count`           | integer | no       | Maximum number of search results to return (1-20). Defaults to 20.          |
| `offset`          | integer | no       | Zero-based page offset for pagination (0-9). Defaults to 0.                 |
| `freshness`       | string  | no       | `"pd"`, `"pw"`, `"pm"`, `"py"`, or a `"YYYY-MM-DDtoYYYY-MM-DD"` range.     |
| `safesearch`      | string  | no       | `"off"`, `"moderate"`, or `"strict"`. Defaults to `"moderate"`.             |
| `country`         | string  | no       | Two-character country code to prefer results from.                          |
| `search_lang`     | string  | no       | ISO 639-1 language code to prefer for result content.                       |
| `extra_snippets`  | boolean | no       | Whether to include up to five additional excerpts per result. Defaults to false. |

News, image, and local Brave endpoints are intentionally out of scope for v1.

## Provider setup

All tools read their API credentials from Laravel's `services` config and their optional defaults from the `ai` config.

### 1. Add the Brave service to `config/services.php`

```php
// config/services.php

return [

    // ... existing services ...

    'brave' => [
        'key' => env('BRAVE_API_KEY'),
    ],

];
```

### 2. Add toolkit defaults to `config/ai.php`

```php
// config/ai.php

return [

    // ... existing laravel/ai config ...

    'toolkit' => [
        'brave' => [
            'search' => [
                'count' => (int) env('BRAVE_SEARCH_COUNT', 20),
                'offset' => (int) env('BRAVE_SEARCH_OFFSET', 0),
                'safesearch' => env('BRAVE_SEARCH_SAFESEARCH', 'moderate'),
                'freshness' => env('BRAVE_SEARCH_FRESHNESS'),
                'country' => env('BRAVE_SEARCH_COUNTRY'),
                'search_lang' => env('BRAVE_SEARCH_LANG'),
                'extra_snippets' => (bool) env('BRAVE_SEARCH_EXTRA_SNIPPETS', false),
            ],
        ],
    ],

];
```

### 3. Add environment variables to `.env`

```dotenv
BRAVE_API_KEY=your-key-here

# Search defaults
BRAVE_SEARCH_COUNT=20
BRAVE_SEARCH_OFFSET=0
BRAVE_SEARCH_SAFESEARCH=moderate
# BRAVE_SEARCH_FRESHNESS=pw
# BRAVE_SEARCH_COUNTRY=US
# BRAVE_SEARCH_LANG=en
BRAVE_SEARCH_EXTRA_SNIPPETS=false
```

| Config key | Env var | Default | Description |
|---|---|---|---|
| `services.brave.key` | `BRAVE_API_KEY` | - | **Required.** Your Brave Search API key. |
| `ai.toolkit.brave.search.count` | `BRAVE_SEARCH_COUNT` | `20` | Default search results (1-20). |
| `ai.toolkit.brave.search.offset` | `BRAVE_SEARCH_OFFSET` | `0` | Default page offset (0-9). |
| `ai.toolkit.brave.search.safesearch` | `BRAVE_SEARCH_SAFESEARCH` | `"moderate"` | `"off"`, `"moderate"`, or `"strict"`. |
| `ai.toolkit.brave.search.freshness` | `BRAVE_SEARCH_FRESHNESS` | - | Optional freshness filter. |
| `ai.toolkit.brave.search.country` | `BRAVE_SEARCH_COUNTRY` | - | Optional 2-letter country code. |
| `ai.toolkit.brave.search.search_lang` | `BRAVE_SEARCH_LANG` | - | Optional ISO 639-1 language code. |
| `ai.toolkit.brave.search.extra_snippets` | `BRAVE_SEARCH_EXTRA_SNIPPETS` | `false` | Include additional excerpts. |

## Safety

- All tools validate required inputs before calling the API.
- Numeric parameters are clamped to their valid ranges; enums fall back to safe defaults.
- Invalid freshness values are omitted rather than sent to the API.
- API errors are caught and returned as friendly string messages.
- Requires a valid Brave Search API key (`X-Subscription-Token`).

## Brave Search API

These tools use the [Brave Search API](https://api-dashboard.search.brave.com/). Brave offers a free tier to get started.

Full API reference:

- [Web Search Endpoint](https://api-dashboard.search.brave.com/api-reference/web/search/get)
