<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$org = getenv('GITHUB_ORG') ?: 'shipfastlabs';

$only = $argv[1] ?? null;

$marker = '<!-- AUTO-GENERATED: do not edit above this line. Run `php tools/docgen.php`. -->';

$body = <<<'MD'
## Installation

```bash
composer require %PACKAGE%
```

## Usage

Add the tool to an agent's `tools()`:

```php
use %CLASS%;

$tools = [new %SHORT_CLASS%];
```

## Input schema

<!-- Document each schema parameter: name, type, whether required, and what it does. -->

## Configuration

<!-- If the tool needs config, read it from config('ai.toolkit.<tool>.<key>') and document the keys here for the user
     to add to their config/ai.php manually. Tools do NOT ship config files or service providers. -->
<!-- Remove this section entirely for pure tools that need no configuration. -->

## Safety

<!-- Note any guardrails: allow-lists, read-only enforcement, size caps, timeouts. -->
MD;

$folders = $only !== null
    ? [$root.'/src/'.$only]
    : (glob($root.'/src/*', GLOB_ONLYDIR) ?: []);

foreach ($folders as $path) {
    $folder = basename($path);

    if (! is_file($path.'/composer.json')) {
        continue;
    }

    $composer = (array) json_decode((string) file_get_contents($path.'/composer.json'), true);
    $package = (string) ($composer['name'] ?? "{$org}/toolkit-".strtolower($folder));
    $description = (string) ($composer['description'] ?? "The {$folder} tool for the Laravel AI SDK");

    $namespace = '';

    foreach ((array) ($composer['autoload']['psr-4'] ?? []) as $ns => $dir) {
        $namespace = rtrim((string) $ns, '\\');
    }

    $shortClass = $folder === 'stub' ? 'StubTool' : "{$folder}Tool";
    $class = $namespace !== '' ? "{$namespace}\\{$shortClass}" : $shortClass;

    $header = "# {$package}\n\n"
        ."[![Latest Version](https://img.shields.io/packagist/v/{$package}.svg)](https://packagist.org/packages/{$package})\n"
        ."[![Total Downloads](https://img.shields.io/packagist/dt/{$package}.svg)](https://packagist.org/packages/{$package})\n\n"
        ."> {$description}\n\n"
        ."Part of the [{$org}/toolkit](https://github.com/{$org}/toolkit) catalog of reusable AI tools for the Laravel AI SDK.\n\n"
        .$marker."\n";

    $generated = strtr($body, [
        '%PACKAGE%' => $package,
        '%CLASS%' => $class,
        '%SHORT_CLASS%' => $shortClass,
    ]);

    $readmePath = $path.'/README.md';
    $existing = is_file($readmePath) ? (string) file_get_contents($readmePath) : '';

    $authored = str_contains($existing, $marker)
        ? substr($existing, strpos($existing, $marker) + strlen($marker))
        : "\n".$generated;

    file_put_contents($readmePath, $header.$authored);
    echo "✓ {$readmePath}\n";
}
