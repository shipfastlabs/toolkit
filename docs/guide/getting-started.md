# Getting Started

The toolkit is a community catalog of reusable AI tools for the [Laravel AI SDK](https://github.com/laravel/ai). The
tools are developed in one monorepo and split into small, independently installable packages.

A tool is a class implementing `Laravel\Ai\Contracts\Tool`:

```php
interface Tool
{
    public function description(): Stringable|string;
    public function handle(Request $request): Stringable|string;
    public function schema(JsonSchema $schema): array;
}
```

## Installation

Each tool is its own package. Require only the ones you need:

```bash
composer require shipfastlabs/toolkit-calculator
composer require shipfastlabs/toolkit-database
```

### Requirements

- PHP 8.4+
- [`laravel/ai`](https://github.com/laravel/ai)

### Configuration

Tools do not ship their own config files. Configurable tools read from the `ai.toolkit.<tool>` key of the Laravel AI
SDK's existing `config/ai.php`, which you add manually. See each tool's page for the exact keys. Pure tools such as
the Calculator need no configuration.

## Usage

Instantiate a tool and pass it to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Calculator\CalculatorTool;
use Shipfastlabs\Toolkit\Database\DatabaseQueryTool;

$tools = [
    new CalculatorTool,
    new DatabaseQueryTool,
];
```

Each tool advertises its purpose via `description()` and its inputs via `schema()`; the model decides when to call it.
Tools return their result (or a friendly error message) as a string, so the model can react and recover.

Browse the [Tools](/tools/calculator) section for the full catalog. Each page is generated from that tool's README,
so the docs and the package always agree.
