# Jigsawstack

> JigsawStack tools for the Laravel AI SDK - sentiment, summary, embedding, translation, web search, scraping, vision, audio, and validation

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

## Installation

```bash
composer require shipfastlabs/toolkit-jigsawstack
```

## Usage

Register every JigsawStack tool at once with the `JigsawStack` helper:

```php
use Shipfastlabs\Toolkit\JigsawStack\JigsawStack;

$tools = JigsawStack::all(); // Collection<int, Tool>
```

Or add individual tools to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackWebSearch;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSummary;

$tools = [
    new JigsawStackWebSearch,
    new JigsawStackSummary,
];
```

## Tools

Each tool maps to a JigsawStack endpoint and returns the raw JSON response (pretty-printed) so the model can read every field.

### General

| Class | Endpoint | Required | Optional |
|---|---|---|---|
| `JigsawStackSentiment` | `POST /v1/ai/sentiment` | `text` | — |
| `JigsawStackSummary` | `POST /v1/ai/summary` | `text` | `type` (`text`\|`points`), `max_points` |
| `JigsawStackEmbedding` | `POST /v1/embedding` | `text` | `type` (default `text`) |
| `JigsawStackPrediction` | `POST /v1/ai/prediction` | `dataset` (JSON array of `{date,value}`) | `steps` |
| `JigsawStackTextToSql` | `POST /v1/ai/sql` | `prompt` | `sql_schema`, `database` |

### Translation

| Class | Endpoint | Required | Optional |
|---|---|---|---|
| `JigsawStackTranslateText` | `POST /v1/ai/translate` | `text`, `target_language` | `current_language` |
| `JigsawStackTranslateImage` | `POST /v1/ai/translate/image` | `url`, `target_language` | — |

### Web

| Class | Endpoint | Required | Optional |
|---|---|---|---|
| `JigsawStackWebSearch` | `POST /v1/web/search` | `query` | `ai_overview`, `safe_search`, `max_results` |
| `JigsawStackAiScrape` | `POST /v1/ai/scrape` | `url`, `element_prompts` (comma-separated) | `root_element_selector` |
| `JigsawStackHtmlToAny` | `POST /v1/web/html_to_any` | `html` | `type` (`png`\|`jpeg`\|`webp`\|`pdf`), `full_page` |
| `JigsawStackSearchSuggestions` | `GET /v1/web/search/suggest` | `query` | — |

### Vision

| Class | Endpoint | Required | Optional |
|---|---|---|---|
| `JigsawStackVocr` | `POST /v1/vocr` | `url` | `prompt` |
| `JigsawStackObjectDetection` | `POST /v1/object_detection` | `url` | `prompts` (comma-separated), `annotated_image` |

### Audio

| Class | Endpoint | Required | Optional |
|---|---|---|---|
| `JigsawStackSpeechToText` | `POST /v1/ai/transcribe` | `url` | `language`, `translate` |

### Validation

| Class | Endpoint | Required | Optional |
|---|---|---|---|
| `JigsawStackNsfwDetection` | `POST /v1/validate/nsfw` | `url` | — |
| `JigsawStackProfanityCheck` | `POST /v1/validate/profanity` | `text` | `censor_replacement` |
| `JigsawStackSpellCheck` | `POST /v1/validate/spell_check` | `text` | `language_code` |
| `JigsawStackSpamCheck` | `POST /v1/validate/spam_check` | `text` | — |

## Configuration

Every tool reads its API key from Laravel's `services` config.

### 1. Add the JigsawStack service to `config/services.php`

```php
// config/services.php

return [

    // ... existing services ...

    'jigsawstack' => [
        'key' => env('JIGSAWSTACK_API_KEY'),
    ],

];
```

### 2. Add the environment variable to `.env`

```dotenv
JIGSAWSTACK_API_KEY=your-key-here
```

| Config key | Env var | Default | Description |
|---|---|---|---|
| `services.jigsawstack.key` | `JIGSAWSTACK_API_KEY` | - | **Required.** Your JigsawStack API key, sent as the `x-api-key` header. |

## Safety

- All tools validate required inputs before calling the API.
- Requests time out after 60 seconds.
- API errors and network failures are caught and returned as friendly string messages so the model can recover.
- Requires a valid JigsawStack API key; tools return a clear "not configured" message when it is missing.

## JigsawStack API

These tools use the [JigsawStack API](https://jigsawstack.com/docs). See the [API reference](https://jigsawstack.com/docs/api-reference) for full details on each endpoint and its response shape.
