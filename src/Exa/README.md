# shipfastlabs/toolkit-exa

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-exa.svg)](https://packagist.org/packages/shipfastlabs/toolkit-exa)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-exa.svg)](https://packagist.org/packages/shipfastlabs/toolkit-exa)

> Exa tools for the Laravel AI SDK - Search, Find Similar, Get Contents, and Answer

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->


## Installation

```bash
composer require shipfastlabs/toolkit-exa
```

## Usage

Register every Exa tool at once with the `Exa` helper:

```php
use Shipfastlabs\Toolkit\Exa\Exa;

$tools = Exa::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Exa\ExaSearch;
use Shipfastlabs\Toolkit\Exa\ExaFindSimilar;
use Shipfastlabs\Toolkit\Exa\ExaGetContents;
use Shipfastlabs\Toolkit\Exa\ExaAnswer;

$tools = [
    new ExaSearch,
    new ExaFindSimilar,
    new ExaGetContents,
    new ExaAnswer,
];
```

## Tools

### ExaSearch

Search the web with Exa's embeddings-based search engine.

| Parameter         | Type    | Required | Description                                                                 |
|-------------------|---------|----------|-----------------------------------------------------------------------------|
| `query`           | string  | yes      | The search query to look up on the web.                                     |
| `type`            | string  | no       | `"auto"`, `"keyword"`, `"neural"`, `"fast"`, or `"deep"`. Defaults to `"auto"`. |
| `num_results`     | integer | no       | Number of results to return (1-25). Defaults to 10.                         |
| `category`        | string  | no       | Focus a category, e.g. `"research paper"`, `"news"`, `"github"`, `"pdf"`.   |
| `include_domains` | string  | no       | Comma-separated domains to restrict results to.                            |
| `exclude_domains` | string  | no       | Comma-separated domains to exclude from results.                           |
| `include_text`    | boolean | no       | Whether to include each result's full page text. Defaults to true.          |

### ExaFindSimilar

Find pages semantically similar to a given URL.

| Parameter         | Type    | Required | Description                                                                 |
|-------------------|---------|----------|-----------------------------------------------------------------------------|
| `url`             | string  | yes      | The URL to find similar pages for.                                          |
| `num_results`     | integer | no       | Number of similar results to return (1-25). Defaults to 10.                 |
| `include_domains` | string  | no       | Comma-separated domains to restrict results to.                            |
| `exclude_domains` | string  | no       | Comma-separated domains to exclude from results.                           |
| `include_text`    | boolean | no       | Whether to include each result's full page text. Defaults to true.          |

### ExaGetContents

Retrieve clean, parsed contents from one or more URLs.

| Parameter    | Type    | Required | Description                                                                     |
|--------------|---------|----------|---------------------------------------------------------------------------------|
| `urls`       | string  | yes      | A single URL or comma-separated URLs (max 20) to fetch contents from.           |
| `text`       | boolean | no       | Whether to include the full page text. Defaults to true.                        |
| `summary`    | boolean | no       | Whether to include an AI-generated summary of each page. Defaults to false.     |
| `highlights` | boolean | no       | Whether to include relevant highlighted snippets. Defaults to false.            |
| `livecrawl`  | string  | no       | `"never"`, `"fallback"`, `"always"`, or `"preferred"`. Defaults to `"fallback"`. |

### ExaAnswer

Get a direct, sourced answer to a question with citations.

| Parameter      | Type    | Required | Description                                                              |
|----------------|---------|----------|--------------------------------------------------------------------------|
| `query`        | string  | yes      | The question to answer using web sources.                                |
| `include_text` | boolean | no       | Whether to include the full text of each cited source. Defaults to false. |

## Provider setup

All tools read their API credentials from Laravel's `services` config and their optional defaults from the `ai` config.

### 1. Add the Exa service to `config/services.php`

```php
// config/services.php

return [

    // ... existing services ...

    'exa' => [
        'key' => env('EXA_API_KEY'),
    ],

];
```

### 2. Add toolkit defaults to `config/ai.php`

```php
// config/ai.php

return [

    // ... existing laravel/ai config ...

    'toolkit' => [
        'exa' => [
            'search' => [
                'num_results' => (int) env('EXA_SEARCH_NUM_RESULTS', 10),
                'type' => env('EXA_SEARCH_TYPE', 'auto'),
                'include_text' => (bool) env('EXA_SEARCH_INCLUDE_TEXT', true),
            ],
            'find_similar' => [
                'num_results' => (int) env('EXA_FIND_SIMILAR_NUM_RESULTS', 10),
                'include_text' => (bool) env('EXA_FIND_SIMILAR_INCLUDE_TEXT', true),
            ],
            'contents' => [
                'text' => (bool) env('EXA_CONTENTS_TEXT', true),
                'livecrawl' => env('EXA_CONTENTS_LIVECRAWL', 'fallback'),
            ],
            'answer' => [
                'include_text' => (bool) env('EXA_ANSWER_INCLUDE_TEXT', false),
            ],
        ],
    ],

];
```

### 3. Add environment variables to `.env`

```dotenv
EXA_API_KEY=your-key-here

# Search defaults
EXA_SEARCH_NUM_RESULTS=10
EXA_SEARCH_TYPE=auto
EXA_SEARCH_INCLUDE_TEXT=true

# Find similar defaults
EXA_FIND_SIMILAR_NUM_RESULTS=10
EXA_FIND_SIMILAR_INCLUDE_TEXT=true

# Contents defaults
EXA_CONTENTS_TEXT=true
EXA_CONTENTS_LIVECRAWL=fallback

# Answer defaults
EXA_ANSWER_INCLUDE_TEXT=false
```

| Config key | Env var | Default | Description |
|---|---|---|---|
| `services.exa.key` | `EXA_API_KEY` | - | **Required.** Your Exa API key. |
| `ai.toolkit.exa.search.num_results` | `EXA_SEARCH_NUM_RESULTS` | `10` | Default search results (1-25). |
| `ai.toolkit.exa.search.type` | `EXA_SEARCH_TYPE` | `"auto"` | `"auto"`, `"keyword"`, `"neural"`, `"fast"`, or `"deep"`. |
| `ai.toolkit.exa.search.include_text` | `EXA_SEARCH_INCLUDE_TEXT` | `true` | Include full page text in search results. |
| `ai.toolkit.exa.find_similar.num_results` | `EXA_FIND_SIMILAR_NUM_RESULTS` | `10` | Default similar results (1-25). |
| `ai.toolkit.exa.find_similar.include_text` | `EXA_FIND_SIMILAR_INCLUDE_TEXT` | `true` | Include full page text in similar results. |
| `ai.toolkit.exa.contents.text` | `EXA_CONTENTS_TEXT` | `true` | Include full page text in contents. |
| `ai.toolkit.exa.contents.livecrawl` | `EXA_CONTENTS_LIVECRAWL` | `"fallback"` | `"never"`, `"fallback"`, `"always"`, or `"preferred"`. |
| `ai.toolkit.exa.answer.include_text` | `EXA_ANSWER_INCLUDE_TEXT` | `false` | Include full text of cited sources. |

## Safety

- All tools validate required inputs before calling the API.
- Numeric parameters are clamped to their valid ranges; enums fall back to safe defaults.
- `ExaGetContents` accepts at most 20 URLs per request.
- API errors are caught and returned as friendly string messages.
- Requires a valid Exa API key.

## Exa API

These tools use the [Exa API](https://exa.ai). Exa offers a free tier to get started.

Full API reference:
- [Search Endpoint](https://exa.ai/docs/reference/search)
- [Find Similar Links Endpoint](https://exa.ai/docs/reference/find-similar-links)
- [Get Contents Endpoint](https://exa.ai/docs/reference/get-contents)
- [Answer Endpoint](https://exa.ai/docs/reference/answer)
