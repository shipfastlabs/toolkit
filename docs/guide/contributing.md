# Contributing

Tools are added to the [monorepo](https://github.com/shipfastlabs/toolkit), never to the read-only mirror packages.

1. Copy `stub/` to `src/<YourTool>/` and rename the class, namespace and `composer.json` package.
2. Implement `description()`, `schema()` and `handle()`; read any config from `ai.toolkit.<tool>` (tools ship no config files).
3. Generate the README scaffold: `tools/docgen.sh <YourTool>`.
4. Add the tool's autoload entries to the root `composer.json`, then `composer dump-autoload && composer test` until green (PHPStan max, 100% type + code coverage).
5. Open a PR. A maintainer applies `new-tool` and/or `release:patch|minor|major`.

After merge, a maintainer runs `composer publish` locally to create the mirror, split the folder, tag it and publish
to Packagist. See the full [CONTRIBUTING guide](https://github.com/shipfastlabs/toolkit/blob/main/CONTRIBUTING.md).
