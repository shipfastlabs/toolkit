# shipfastlabs/toolkit-firecrawl

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-firecrawl.svg)](https://packagist.org/packages/shipfastlabs/toolkit-firecrawl)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-firecrawl.svg)](https://packagist.org/packages/shipfastlabs/toolkit-firecrawl)

> Firecrawl tools for the Laravel AI SDK - Scrape, Map, Search, and Crawl

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->

## Installation

```bash
composer require shipfastlabs/toolkit-firecrawl
```

## Usage

Register every Firecrawl tool at once with the `Firecrawl` helper:

```php
use Shipfastlabs\Toolkit\Firecrawl\Firecrawl;

$tools = Firecrawl::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlScrape;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlMap;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlSearch;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlCrawl;
use Shipfastlabs\Toolkit\Firecrawl\FirecrawlCrawlStatus;

$tools = [
    new FirecrawlScrape,
    new FirecrawlMap,
    new FirecrawlSearch,
    new FirecrawlCrawl,
    new FirecrawlCrawlStatus,
];
```

Each tool calls a Firecrawl v2 endpoint and returns the raw JSON response (pretty-printed) so the model can read every field, or a friendly error string it can recover from.

## Tools

### FirecrawlScrape

Scrape a single page into clean, LLM-ready markdown. Renders JavaScript, so it works on dynamic pages.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `url` | string | yes | The URL of the web page to scrape. |
| `formats` | string | no | Comma-separated output formats, any of `markdown`, `html`, `rawHtml`, `links`, `summary` (default: `markdown`). |
| `only_main_content` | boolean | no | Return only the main content, stripping navigation, headers and footers (default: `true`). |

### FirecrawlMap

Map a website and return its list of URLs. Use it to discover every page before deciding what to scrape.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `url` | string | yes | The base URL of the website to map. |
| `search` | string | no | Only return URLs that match this search term (default: no filter). |
| `limit` | integer | no | Maximum number of URLs to return (1-5000, default: 100). |

### FirecrawlSearch

Search the web and return ranked results, optionally with each page scraped into markdown.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `query` | string | yes | The search query to look up on the web. |
| `limit` | integer | no | Maximum number of results to return (1-20, default: 5). |
| `sources` | string | no | Comma-separated result sources, any of `web`, `news`, `images` (default: `web`). |
| `scrape_content` | boolean | no | Scrape each result and include its markdown content, not just the link (default: `false`). |

### FirecrawlCrawl

Start crawling a website. Crawling is **asynchronous**: this tool returns a crawl `id` immediately and does not wait for the crawl to finish. Pass that `id` to `FirecrawlCrawlStatus` to poll progress and collect results.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `url` | string | yes | The URL to start crawling from. |
| `limit` | integer | no | Maximum number of pages to crawl (1-1000, default: 10). |
| `prompt` | string | no | Natural-language instructions to steer the crawl, e.g. `"only blog posts"`. |

### FirecrawlCrawlStatus

Check the status of a crawl started with `FirecrawlCrawl`. Returns the status (`scraping` or `completed`), progress, and the pages crawled so far.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `crawl_id` | string | yes | The crawl id returned by `FirecrawlCrawl`. |

## Configuration

Every tool reads its API key from Laravel's `services` config and its optional defaults from the `ai` config.

### 1. Add the Firecrawl service to `config/services.php`

```php
// config/services.php

return [

    // ... existing services ...

    'firecrawl' => [
        'key' => env('FIRECRAWL_API_KEY'),
    ],

];
```

### 2. Add toolkit defaults to `config/ai.php`

```php
// config/ai.php

return [

    // ... existing laravel/ai config ...

    'toolkit' => [
        'firecrawl' => [
            'scrape' => [
                'formats' => env('FIRECRAWL_SCRAPE_FORMATS', 'markdown'),
                'only_main_content' => (bool) env('FIRECRAWL_SCRAPE_ONLY_MAIN_CONTENT', true),
            ],
            'map' => [
                'limit' => (int) env('FIRECRAWL_MAP_LIMIT', 100),
            ],
            'search' => [
                'limit' => (int) env('FIRECRAWL_SEARCH_LIMIT', 5),
                'sources' => env('FIRECRAWL_SEARCH_SOURCES', 'web'),
            ],
            'crawl' => [
                'limit' => (int) env('FIRECRAWL_CRAWL_LIMIT', 10),
            ],
        ],
    ],

];
```

### 3. Add environment variables to `.env`

```dotenv
FIRECRAWL_API_KEY=fc-your-key-here

# Scrape defaults
FIRECRAWL_SCRAPE_FORMATS=markdown
FIRECRAWL_SCRAPE_ONLY_MAIN_CONTENT=true

# Map defaults
FIRECRAWL_MAP_LIMIT=100

# Search defaults
FIRECRAWL_SEARCH_LIMIT=5
FIRECRAWL_SEARCH_SOURCES=web

# Crawl defaults
FIRECRAWL_CRAWL_LIMIT=10
```

| Config key | Env var | Default | Description |
|---|---|---|---|
| `services.firecrawl.key` | `FIRECRAWL_API_KEY` | - | **Required.** Your Firecrawl API key, sent as a bearer token. |
| `ai.toolkit.firecrawl.scrape.formats` | `FIRECRAWL_SCRAPE_FORMATS` | `"markdown"` | Default scrape formats (comma-separated). |
| `ai.toolkit.firecrawl.scrape.only_main_content` | `FIRECRAWL_SCRAPE_ONLY_MAIN_CONTENT` | `true` | Strip navigation, headers and footers by default. |
| `ai.toolkit.firecrawl.map.limit` | `FIRECRAWL_MAP_LIMIT` | `100` | Default number of URLs to map (1-5000). |
| `ai.toolkit.firecrawl.search.limit` | `FIRECRAWL_SEARCH_LIMIT` | `5` | Default number of search results (1-20). |
| `ai.toolkit.firecrawl.search.sources` | `FIRECRAWL_SEARCH_SOURCES` | `"web"` | Default search sources (comma-separated). |
| `ai.toolkit.firecrawl.crawl.limit` | `FIRECRAWL_CRAWL_LIMIT` | `10` | Default number of pages to crawl (1-1000). |

## Safety

- All tools validate required inputs before calling the API.
- Numeric parameters are clamped to their valid ranges; `formats` and `sources` are filtered against an allow-list and fall back to a safe default.
- `FirecrawlCrawlStatus` accepts only a well-formed crawl id (`[A-Za-z0-9-]`), so a crawl id can never be used to reach another endpoint.
- API errors and network failures are caught and returned as friendly string messages so the model can recover.
- Requires a valid Firecrawl API key; tools return a clear "not configured" message when it is missing.

## Firecrawl API

These tools use the [Firecrawl v2 API](https://docs.firecrawl.dev). Firecrawl offers a free tier to get started.

Full API reference:
- [Scrape Endpoint](https://docs.firecrawl.dev/api-reference/endpoint/scrape)
- [Map Endpoint](https://docs.firecrawl.dev/api-reference/endpoint/map)
- [Search Endpoint](https://docs.firecrawl.dev/api-reference/endpoint/search)
- [Crawl Endpoint](https://docs.firecrawl.dev/api-reference/endpoint/crawl-post)
- [Crawl Status Endpoint](https://docs.firecrawl.dev/api-reference/endpoint/crawl-get)
