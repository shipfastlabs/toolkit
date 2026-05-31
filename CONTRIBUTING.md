# Contributing

Contributions are welcome and accepted via pull requests against the **monorepo**
([`shipfastlabs/toolkit`](https://github.com/shipfastlabs/toolkit)).

> The per-tool packages (`shipfastlabs/toolkit-*`) are **read-only mirrors** with issues and pull requests disabled.
> Never push a tag against them — everything flows from this monorepo. Open all contributions here instead.

## Adding a tool

1. Fork and clone the monorepo, then install the shared dev tooling:
   ```bash
   composer install
   ```
2. Copy the template:
   ```bash
   cp -R stub src/YourTool
   ```
3. In `src/YourTool/`:
   - Rename `StubTool.php` → `YourToolTool.php` and the class to `YourToolTool`.
   - Update the namespace to `Shipfastlabs\Toolkit\YourTool` (in the class, the test and `composer.json`).
   - Set the package name in `composer.json` to `shipfastlabs/toolkit-yourtool`.
   - Implement `description()`, `schema()` and `handle()`.
   - If the tool needs configuration, read it from `config('ai.toolkit.<tool>.<key>')` and document the keys in the
     README for the user to add to their `config/ai.php` manually — tools do **not** ship config files or providers.
4. Generate the README scaffold and fill in the tool-specific sections:
   ```bash
   tools/docgen.sh YourTool
   ```
5. Make the root autoloader aware of the new package, then run the full suite. Add the package's namespace to
   the root `composer.json` (`Shipfastlabs\Toolkit\YourTool\` → `src/YourTool/src/` under `autoload`, and the
   matching `Tests\` entry under `autoload-dev`), then:
   ```bash
   composer dump-autoload
   composer test
   ```
6. Open a PR. A maintainer applies labels:
   - `new-tool` — first release (creates the mirror, tags `1.0.0`).
   - `release:patch` | `release:minor` | `release:major` — the bump for every tool changed in the PR.

On merge to `master`, automation creates the mirror repo, splits the folder (history preserved), tags it and
publishes the release; the Packagist webhook then ships the version.

## Authoring conventions (from `laravel/ai`)

- Apply the `#[Strict]` attribute to tool classes.
- Use the fluent schema: `$schema->string()->description('…')->required()`.
- Use typed request accessors: `$request->string('path')`.
- **Return errors to the model as strings** (`"File [x] does not exist."`) — don't throw; let the LLM recover.
- Write descriptions **for tool-calling** — instructive, and mention constraints.
- Bake in safety guardrails (allow-lists, read-only enforcement, size caps, timeouts).

## Quality gates (enforced by CI)

```bash
composer test           # everything below, in order
composer test:lint      # Pint + Rector
composer test:types     # PHPStan (max)
composer test:type-coverage   # 100% type coverage
composer test:unit      # Pest, 100% code coverage
```

Every tool must pass all of these (PHPStan max, 100% type coverage, 100% code coverage) before it can be split.

## Guidelines

- Send a coherent commit history; each commit should be meaningful.
- We follow [SemVer](https://semver.org/) — one PR carries one bump type for every tool it changes.
- `dev` dependencies live in the **root** `composer.json` only; per-tool `composer.json` files carry no `require-dev`.
