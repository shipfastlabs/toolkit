# Contributing

Tools are added to the [monorepo](https://github.com/shipfastlabs/toolkit), never to the read-only mirror packages.

1. Copy `stub/` to `src/<YourTool>/` and rename the class, namespace and `composer.json` package.
2. Implement `description()`, `schema()` and `handle()`; read any config from `ai.toolkit.<tool>` (tools ship no config files).
3. Generate the README scaffold: `php tools/docgen.php <YourTool>`.
4. Add the tool's autoload entries to the root `composer.json`, then `composer dump-autoload && composer test` until green (PHPStan max, 100% type + code coverage).
5. Open a PR. A maintainer applies `new-tool` and/or `release:patch|minor|major`.

On merge, automation creates the mirror repo, splits the folder, tags it and publishes to Packagist. No manual repo
work. See the full [CONTRIBUTING guide](https://github.com/shipfastlabs/toolkit/blob/master/CONTRIBUTING.md).
