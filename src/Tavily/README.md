# shipfastlabs/toolkit-tavily

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-tavily.svg)](https://packagist.org/packages/shipfastlabs/toolkit-tavily)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-tavily.svg)](https://packagist.org/packages/shipfastlabs/toolkit-tavily)

> Tavily tools for the Laravel AI SDK - Search, Extract, Crawl, and Map

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->


## Installation

```bash
composer require shipfastlabs/toolkit-tavily
```

## Usage

Register every Tavily tool at once with the `Tavily` helper:

```php
use Shipfastlabs\Toolkit\Tavily\Tavily;

$tools = Tavily::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Tavily\TavilySearch;
use Shipfastlabs\Toolkit\Tavily\TavilyExtract;
use Shipfastlabs\Toolkit\Tavily\TavilyCrawl;
use Shipfastlabs\Toolkit\Tavily\TavilyMap;

$tools = [
    new TavilySearch,
    new TavilyExtract,
    new TavilyCrawl,
    new TavilyMap,
];
```

## Tools

### TavilySearch

Search the web for real-time information.

| Parameter     | Type    | Required | Description                                                        |
|---------------|---------|----------|--------------------------------------------------------------------|
| `query`       | string  | yes      | The search query to look up on the web.                            |
| `max_results` | integer | no       | Maximum number of search results to return (1-10). Defaults to 5.  |
| `search_depth` | string | no       | `"basic"` for fast results or `"advanced"` for comprehensive. Defaults to `"basic"`. |
| `include_answer` | boolean | no    | Whether to include a concise AI-generated answer. Defaults to false. |

### TavilyExtract

Extract clean, structured content from URLs.

| Parameter     | Type    | Required | Description                                                        |
|---------------|---------|----------|--------------------------------------------------------------------|
| `urls`        | string  | yes      | A single URL or comma-separated URLs to extract content from.       |
| `query`       | string  | no       | Optional query to rerank extracted chunks by relevance.            |
| `extract_depth` | string | no     | `"basic"` or `"advanced"`. Defaults to `"basic"`.                  |
| `format`      | string  | no       | `"markdown"` or `"text"`. Defaults to `"markdown"`.                |
| `include_images` | boolean | no    | Whether to include images extracted from URLs. Defaults to false.  |

### TavilyCrawl

Intelligently crawl a website and extract content.

| Parameter     | Type    | Required | Description                                                        |
|---------------|---------|----------|--------------------------------------------------------------------|
| `url`         | string  | yes      | The root URL to begin the crawl from.                              |
| `instructions` | string | no       | Optional natural language instructions for the crawler.            |
| `max_depth`   | integer | no       | Maximum crawl depth (1-5). Defaults to 1.                        |
| `max_breadth` | integer | no       | Maximum links to follow per page (1-500). Defaults to 20.         |
| `limit`       | integer | no       | Total number of links to process. Defaults to 50.                 |
| `extract_depth` | string | no     | `"basic"` or `"advanced"`. Defaults to `"basic"`.                  |
| `allow_external` | boolean | no    | Whether to allow crawling external domains. Defaults to false.      |

### TavilyMap

Discover and map a website's structure.

| Parameter     | Type    | Required | Description                                                        |
|---------------|---------|----------|--------------------------------------------------------------------|
| `url`         | string  | yes      | The root URL to begin the mapping from.                            |
| `instructions` | string | no       | Optional natural language instructions for the crawler.            |
| `max_depth`   | integer | no       | Maximum map depth (1-5). Defaults to 1.                            |
| `max_breadth` | integer | no       | Maximum links to follow per page (1-500). Defaults to 20.         |
| `limit`       | integer | no       | Total number of links to process. Defaults to 50.                 |
| `allow_external` | boolean | no    | Whether to allow crawling external domains. Defaults to false.      |

## Provider setup

All tools read their API credentials from Laravel's `services` config and their optional defaults from the `ai` config.

### 1. Add the Tavily service to `config/services.php`

```php
// config/services.php

return [

    // ... existing services ...

    'tavily' => [
        'key' => env('TAVILY_API_KEY'),
    ],

];
```

### 2. Add toolkit defaults to `config/ai.php`

```php
// config/ai.php

return [

    // ... existing laravel/ai config ...

    'toolkit' => [
        'tavily' => [
            'search' => [
                'max_results' => (int) env('TAVILY_SEARCH_MAX_RESULTS', 5),
                'search_depth' => env('TAVILY_SEARCH_DEPTH', 'basic'),
                'include_answer' => (bool) env('TAVILY_SEARCH_INCLUDE_ANSWER', false),
            ],
            'extract' => [
                'extract_depth' => env('TAVILY_EXTRACT_DEPTH', 'basic'),
                'format' => env('TAVILY_EXTRACT_FORMAT', 'markdown'),
            ],
            'crawl' => [
                'max_depth' => (int) env('TAVILY_CRAWL_MAX_DEPTH', 1),
                'max_breadth' => (int) env('TAVILY_CRAWL_MAX_BREADTH', 20),
                'limit' => (int) env('TAVILY_CRAWL_LIMIT', 50),
                'extract_depth' => env('TAVILY_CRAWL_EXTRACT_DEPTH', 'basic'),
            ],
            'map' => [
                'max_depth' => (int) env('TAVILY_MAP_MAX_DEPTH', 1),
                'max_breadth' => (int) env('TAVILY_MAP_MAX_BREADTH', 20),
                'limit' => (int) env('TAVILY_MAP_LIMIT', 50),
            ],
        ],
    ],

];
```

### 3. Add environment variables to `.env`

```dotenv
TAVILY_API_KEY=tvly-your-key-here

# Search defaults
TAVILY_SEARCH_MAX_RESULTS=5
TAVILY_SEARCH_DEPTH=basic
TAVILY_SEARCH_INCLUDE_ANSWER=false

# Extract defaults
TAVILY_EXTRACT_DEPTH=basic
TAVILY_EXTRACT_FORMAT=markdown

# Crawl defaults
TAVILY_CRAWL_MAX_DEPTH=1
TAVILY_CRAWL_MAX_BREADTH=20
TAVILY_CRAWL_LIMIT=50
TAVILY_CRAWL_EXTRACT_DEPTH=basic

# Map defaults
TAVILY_MAP_MAX_DEPTH=1
TAVILY_MAP_MAX_BREADTH=20
TAVILY_MAP_LIMIT=50
```

| Config key | Env var | Default | Description |
|---|---|---|---|
| `services.tavily.key` | `TAVILY_API_KEY` | - | **Required.** Your Tavily API key. |
| `ai.toolkit.tavily.search.max_results` | `TAVILY_SEARCH_MAX_RESULTS` | `5` | Default search results (1-10). |
| `ai.toolkit.tavily.search.search_depth` | `TAVILY_SEARCH_DEPTH` | `"basic"` | `"basic"` or `"advanced"`. |
| `ai.toolkit.tavily.search.include_answer` | `TAVILY_SEARCH_INCLUDE_ANSWER` | `false` | Default for AI-generated answer. |
| `ai.toolkit.tavily.extract.extract_depth` | `TAVILY_EXTRACT_DEPTH` | `"basic"` | `"basic"` or `"advanced"`. |
| `ai.toolkit.tavily.extract.format` | `TAVILY_EXTRACT_FORMAT` | `"markdown"` | `"markdown"` or `"text"`. |
| `ai.toolkit.tavily.crawl.max_depth` | `TAVILY_CRAWL_MAX_DEPTH` | `1` | Max crawl depth (1-5). |
| `ai.toolkit.tavily.crawl.max_breadth` | `TAVILY_CRAWL_MAX_BREADTH` | `20` | Max links per page (1-500). |
| `ai.toolkit.tavily.crawl.limit` | `TAVILY_CRAWL_LIMIT` | `50` | Total links to process. |
| `ai.toolkit.tavily.crawl.extract_depth` | `TAVILY_CRAWL_EXTRACT_DEPTH` | `"basic"` | `"basic"` or `"advanced"`. |
| `ai.toolkit.tavily.map.max_depth` | `TAVILY_MAP_MAX_DEPTH` | `1` | Max map depth (1-5). |
| `ai.toolkit.tavily.map.max_breadth` | `TAVILY_MAP_MAX_BREADTH` | `20` | Max links per page (1-500). |
| `ai.toolkit.tavily.map.limit` | `TAVILY_MAP_LIMIT` | `50` | Total links to process. |

## Safety

- All tools validate required inputs before calling the API.
- Numeric parameters are clamped to their valid ranges.
- API errors are caught and returned as friendly string messages.
- Requires a valid Tavily API key.

## Tavily API

These tools use the Tavily API. Tavily offers a generous free tier with 1,000 API credits per month.

Full API reference:
- [Search Endpoint](https://docs.tavily.com/documentation/api-reference/endpoint/search)
- [Extract Endpoint](https://docs.tavily.com/documentation/api-reference/endpoint/extract)
- [Crawl Endpoint](https://docs.tavily.com/documentation/api-reference/endpoint/crawl)
- [Map Endpoint](https://docs.tavily.com/documentation/api-reference/endpoint/map)
