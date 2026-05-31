# shipfastlabs/toolkit-stub

[![Latest Version](https://img.shields.io/packagist/v/shipfastlabs/toolkit-stub.svg)](https://packagist.org/packages/shipfastlabs/toolkit-stub)
[![Total Downloads](https://img.shields.io/packagist/dt/shipfastlabs/toolkit-stub.svg)](https://packagist.org/packages/shipfastlabs/toolkit-stub)

> Template tool for the Laravel AI SDK. Copy this folder to start a new tool

Part of the [shipfastlabs/toolkit](https://github.com/shipfastlabs/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

<!-- AUTO-GENERATED: do not edit above this line. Run `php tools/docgen.php`. -->

## Installation

```bash
composer require shipfastlabs/toolkit-stub
```

## Usage

Add the tool to an agent's `tools()`:

```php
use Shipfastlabs\Toolkit\Stub\StubTool;

$tools = [new StubTool];
```

## Input schema

<!-- Document each schema parameter: name, type, whether required, and what it does. -->

## Configuration

<!-- If the tool ships config, document the publishable file and env vars here. -->

## Safety

<!-- Note any guardrails: allow-lists, read-only enforcement, size caps, timeouts. -->