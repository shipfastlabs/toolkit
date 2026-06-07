# shipfastlabs/toolkit-perplexity

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-perplexity.svg)](https://packagist.org/packages/shipfastlabs/toolkit-perplexity)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-perplexity.svg)](https://packagist.org/packages/shipfastlabs/toolkit-perplexity)

> Perplexity tools for the Laravel AI SDK - Search and Ask (Sonar)

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->

## Installation

```bash
composer require shipfastlabs/toolkit-perplexity
```

## Usage

Register every Perplexity tool at once with the `Perplexity` helper:

```php
use Shipfastlabs\Toolkit\Perplexity\Perplexity;

$tools = Perplexity::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Perplexity\PerplexitySearch;
use Shipfastlabs\Toolkit\Perplexity\PerplexityAsk;

$tools = [
    new PerplexitySearch,
    new PerplexityAsk,
];
```

## Tools

### PerplexitySearch

Search the web for real-time information and get a ranked list of sources. Uses the [Perplexity Search API](https://docs.perplexity.ai/api-reference/search-post).

| Parameter | Type | Required | Description |
|---|---|---|---|
| `query` | string | yes | The search query to look up on the web. |
| `max_results` | integer | no | Maximum number of results to return (1-20). Defaults to 10. |
| `search_recency_filter` | string | no | Restrict results to a recent window: `"hour"`, `"day"`, `"week"`, `"month"`, or `"year"`. No filter by default. |

### PerplexityAsk

Ask a question and get a direct, cited answer grounded in a live web search using Perplexity's [Sonar models](https://docs.perplexity.ai/api-reference/chat-completions-post). The response includes the answer text plus the `search_results` and `citations` used.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `query` | string | yes | The question to ask Perplexity. |
| `model` | string | no | Sonar model: `"sonar"`, `"sonar-pro"`, `"sonar-reasoning"`, `"sonar-reasoning-pro"`, or `"sonar-deep-research"`. Defaults to `"sonar"`. |
| `search_mode` | string | no | `"web"` for the open web, `"academic"` for scholarly sources, or `"sec"` for SEC filings. Defaults to `"web"`. |

## Provider setup

Both tools read their API key from Laravel's `services` config and their optional defaults from the `ai` config.

### 1. Add the Perplexity service to `config/services.php`

```php
// config/services.php

return [

    // ... existing services ...

    'perplexity' => [
        'key' => env('PERPLEXITY_API_KEY'),
    ],

];
```

### 2. Add toolkit defaults to `config/ai.php`

```php
// config/ai.php

return [

    // ... existing laravel/ai config ...

    'toolkit' => [
        'perplexity' => [
            'search' => [
                'max_results' => (int) env('PERPLEXITY_SEARCH_MAX_RESULTS', 10),
                'recency' => env('PERPLEXITY_SEARCH_RECENCY'),
            ],
            'ask' => [
                'model' => env('PERPLEXITY_ASK_MODEL', 'sonar'),
                'search_mode' => env('PERPLEXITY_ASK_SEARCH_MODE', 'web'),
            ],
        ],
    ],

];
```

### 3. Add environment variables to `.env`

```dotenv
PERPLEXITY_API_KEY=pplx-your-key-here

# Search defaults
PERPLEXITY_SEARCH_MAX_RESULTS=10
PERPLEXITY_SEARCH_RECENCY=

# Ask defaults
PERPLEXITY_ASK_MODEL=sonar
PERPLEXITY_ASK_SEARCH_MODE=web
```

| Config key | Env var | Default | Description |
|---|---|---|---|
| `services.perplexity.key` | `PERPLEXITY_API_KEY` | - | **Required.** Your Perplexity API key. |
| `ai.toolkit.perplexity.search.max_results` | `PERPLEXITY_SEARCH_MAX_RESULTS` | `10` | Default search results (1-20). |
| `ai.toolkit.perplexity.search.recency` | `PERPLEXITY_SEARCH_RECENCY` | - | Default recency filter (`hour`/`day`/`week`/`month`/`year`). |
| `ai.toolkit.perplexity.ask.model` | `PERPLEXITY_ASK_MODEL` | `"sonar"` | Default Sonar model. |
| `ai.toolkit.perplexity.ask.search_mode` | `PERPLEXITY_ASK_SEARCH_MODE` | `"web"` | Default search mode (`web`/`academic`/`sec`). |

## Safety

- Both tools validate required inputs before calling the API.
- `max_results` is clamped to its valid 1-20 range.
- `model`, `search_mode`, and `search_recency_filter` are validated against allow-lists, falling back to safe defaults.
- API errors are caught and returned as friendly string messages.
- Requires a valid Perplexity API key.

## Perplexity API

These tools use the [Perplexity API](https://docs.perplexity.ai). You'll need an API key from your [Perplexity account settings](https://www.perplexity.ai/account/api).

Full API reference:
- [Search Endpoint](https://docs.perplexity.ai/api-reference/search-post)
- [Chat Completions Endpoint](https://docs.perplexity.ai/api-reference/chat-completions-post)
