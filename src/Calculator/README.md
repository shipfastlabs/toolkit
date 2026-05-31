# shipfastlabs/toolkit-calculator

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-calculator.svg)](https://packagist.org/packages/shipfastlabs/toolkit-calculator)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-calculator.svg)](https://packagist.org/packages/shipfastlabs/toolkit-calculator)

> Calculator tool for the Laravel AI SDK

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->

## Installation

```bash
composer require shipfastlabs/toolkit-calculator
```

## Usage

Instantiate the tool and pass it to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Calculator\CalculatorTool;

$tools = [new CalculatorTool];
```

## Input schema

| Parameter | Type | Required | Description |
|---|---|---|---|
| `expression` | string | yes | The mathematical expression to evaluate, e.g. `"3 * (4 + 1)"`. |

Supports `+`, `-`, `*`, `/`, `%`, `^` (exponent, right-associative), parentheses, unary `+`/`-` and decimal numbers.

## Configuration

None. The calculator is pure and ships no config or service provider.

## Safety

The expression is parsed by a small recursive-descent evaluator; PHP's `eval()` is never used. Invalid input, division or modulo by zero, and non-finite results are returned to the model as plain strings rather than thrown, so the model can recover.
