<p align="center">
    <p align="center">
        <a href="https://github.com/shipfastlabs/toolkit/actions"><img alt="GitHub Workflow Status (main)" src="https://github.com/shipfastlabs/toolkit/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://github.com/shipfastlabs/toolkit"><img alt="License" src="https://img.shields.io/github/license/shipfastlabs/toolkit"></a>
    </p>
</p>

------

# shipfastlabs/toolkit

A **community catalog of reusable AI tools for the [Laravel AI SDK](https://github.com/laravel/ai)**: one development
**monorepo** that **subtree-splits** into many tiny, independently-installable packages.

A "tool" is a small class implementing `Laravel\Ai\Contracts\Tool` (`description()`, `schema()`, `handle()`). This
repo ships a curated, tested set of them so you never re-write the same building blocks again.

> **Requires [PHP 8.4+](https://php.net/releases/)** and [`laravel/ai`](https://github.com/laravel/ai).

## 📚 Documentation

The full catalog, install instructions and per-tool guides live on the documentation site:

**[toolkit.shipfastlabs.com](https://toolkit.shipfastlabs.com)**

This README is for **maintaining** the monorepo: how tools are structured, and how the split + release automation
works.

## Architecture

```
shipfastlabs/toolkit              ← dev monorepo (this repo, NOT installed directly)
├── stub/                         → template to copy when adding a tool (NOT under src/, never split)
└── src/
    ├── Calculator/               → split → shipfastlabs/toolkit-calculator
    └── Database/                 → split → shipfastlabs/toolkit-database
```

- **Develop** in the monorepo: one `composer install`, one test suite, shared tooling.
- **Ship** each `src/<Tool>/` as its own read-only Packagist package via a subtree split (automated on merge to `main`).
- Each tool package `require`s only `laravel/ai`; there is no shared core dependency.
- The split + release machinery lives in [`tools/`](tools/) and [`.github/workflows/`](.github/workflows/).

## Local development

The canonical test run is the monorepo. The root `composer.json` autoload already maps every tool's
`src/<Tool>/src/` namespace, so one install runs the whole suite:

```bash
composer install
composer test         # pint + rector + phpstan + pest + 100% type coverage
```

> When you add a tool, add its `Shipfastlabs\Toolkit\<Tool>\` → `src/<Tool>/src/` entry (and the matching
> `Tests\` entry) to the root `composer.json` `autoload` / `autoload-dev` blocks, then `composer dump-autoload`.

## How releases work

Split repositories (`shipfastlabs/toolkit-*`) are **read-only mirrors**. Nobody commits or tags them directly: code
*and* releases are driven entirely from this monorepo through **PR labels**. Each tool is versioned
**independently** (`http@1.4` while `calculator@1.0`), and **only the tools that changed in a commit get released**.

### Labels

A maintainer applies these to the PR before merging:

| Label | Effect |
|---|---|
| `new-tool` | A brand-new `src/<Tool>/` folder. Creates its mirror repo and tags it `1.0.0`. |
| `release:patch` | Bump the patch version of every tool changed in the PR. |
| `release:minor` | Bump the minor version of every tool changed in the PR. |
| `release:major` | Bump the major version of every tool changed in the PR. |

One PR carries **one** bump type, applied to every tool it touched.

### Adding a new tool

```
1. Copy stub/ → src/<YourTool>/; rename the class, namespace and composer.json package.
2. Implement description(), schema(), handle(); generate the README with `tools/docgen.sh <YourTool>`.
3. Add the tool's autoload entries to the root composer.json, then: composer dump-autoload && composer test  # until green
4. Open a PR → tests.yml runs on it.
5. Maintainer adds `new-tool` (+ optionally `release:minor`), then merges to main.
```

On merge, automation creates `shipfastlabs/toolkit-<tool>`, splits the folder (history preserved), tags it `1.0.0`
and publishes it to Packagist. **No manual repo work.**

### Releasing an existing tool (bugfix / feature)

Identical to above, **minus** `new-tool`. Add `release:patch` (or `minor` / `major`) and merge. On merge, only that
tool's mirror gets a new tag; every other package is untouched. Users update with
`composer update shipfastlabs/toolkit-<tool>`.

### What fires automatically on merge to `main`

```
push:main
 ├─ monorepo-split.yml
 │   ├─ create-repos.sh   → detects NEW src/* folders (PR had `new-tool`) → creates the mirror repo,
 │   │                       sets topics/description, registers it on Packagist
 │   └─ split.sh          → splitsh-lite splits every CHANGED src/<Tool>/ → force-pushes to its mirror
 │
 ├─ release.yml  (after split succeeds)
 │   └─ release.sh        → reads the PR's `release:*` label, diff-trees the changed folders, reads each
 │                          mirror's latest tag, bumps it (or 1.0.0 for `new-tool`), publishes a GitHub Release
 │                          → the Packagist webhook on the mirror sees the tag and ships the version
 │
 └─ docs.yml              → sync-docs.sh refreshes docs/tools/ → builds & deploys the docs site
```

### Invariants that keep it sane at 100+ packages

- **No `version` field in the tool packages** — their versions are git tags on the mirrors; `release.sh` reads and bumps them. (The dev-only monorepo root pins a static `version` purely to silence Composer's root-version notice; it is never published.)
- **Mirrors are read-only** — never commit or tag them by hand; everything flows from the monorepo.
- **Changed-only** — `git diff-tree` guarantees untouched tools never re-release.
- **You manage exactly one repo** — adding or releasing N tools never means touching N repos.

See [CONTRIBUTING.md](CONTRIBUTING.md) for the contributor-facing version of this flow.

**shipfastlabs/toolkit** was created by **[Shipfastlabs](https://shipfastlabs.com)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
