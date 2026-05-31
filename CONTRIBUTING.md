# Contributing

Contributions are welcome and accepted via pull requests against the **monorepo**
([`shipfastlabs/toolkit`](https://github.com/shipfastlabs/toolkit)).

> The per-tool packages (`shipfastlabs/toolkit-*`) are **read-only mirrors** with issues and pull requests disabled.
> Never push a tag against them; everything flows from this monorepo. Open all contributions here instead.

## Repository layout

```
shipfastlabs/toolkit          ← dev monorepo (not installed directly)
├── stub/                     → template; copy to start a tool (never split)
├── tools/                    → publish scripts (split, release, mirrors, docs)
└── src/
    ├── Calculator/           → splits to shipfastlabs/toolkit-calculator
    └── */                    → splits to shipfastlabs/toolkit-*
```

Each `src/<Tool>/` is split into its own read-only mirror repo and published to Packagist, requiring only
`laravel/ai`. You develop and test everything in the monorepo; the mirrors are generated, never edited.

## Local development

The root `composer.json` autoload maps every tool, so one install runs the whole suite:

```bash
composer install
composer test     # pint + rector + phpstan + pest, 100% type & code coverage
```

CI (`tests.yml`) runs the same suite on every push and pull request.

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
   - Rename `StubTool.php` to `YourToolTool.php` and the class to `YourToolTool`.
   - Update the namespace to `Shipfastlabs\Toolkit\YourTool` (in the class, the test and `composer.json`).
   - Set the package name in `composer.json` to `shipfastlabs/toolkit-yourtool`.
   - Implement `description()`, `schema()` and `handle()`.
   - If the tool needs configuration, read it from `config('ai.toolkit.<tool>.<key>')` and document the keys in the
     README for the user to add to their `config/ai.php` manually. Tools do **not** ship config files or providers.
4. Generate the README scaffold and fill in the tool-specific sections:
   ```bash
   tools/docgen.sh YourTool
   ```
5. Make the root autoloader aware of the new package, then run the full suite. Add the package's namespace to the
   root `composer.json` (`Shipfastlabs\Toolkit\YourTool\` to `src/YourTool/src/` under `autoload`, and the matching
   `Tests\` entry under `autoload-dev`), then:
   ```bash
   composer dump-autoload
   composer test
   ```
6. Open a PR. A maintainer applies labels:
   - `new-tool`: first release (creates the mirror, tags `1.0.0`).
   - `release:patch` | `release:minor` | `release:major`: the bump for every tool changed in the PR.

## Authoring conventions (from `laravel/ai`)

- Apply the `#[Strict]` attribute to tool classes.
- Use the fluent schema: `$schema->string()->description('…')->required()`.
- Use typed request accessors: `$request->string('path')`.
- Return errors to the model as strings (`"File [x] does not exist."`); do not throw, so the model can recover.
- Write descriptions for tool-calling: instructive, and mention constraints.
- Bake in safety guardrails (allow-lists, read-only enforcement, size caps, timeouts).

## Quality gates (enforced by CI)

```bash
composer test                 # everything below, in order
composer test:lint            # Pint + Rector
composer test:types           # PHPStan (max)
composer test:type-coverage   # 100% type coverage
composer test:unit            # Pest, 100% code coverage
```

Every tool must pass all of these (PHPStan max, 100% type coverage, 100% code coverage) before it can be split.

## Releasing

Publishing is run locally by a maintainer, so there are no CI secrets to manage. Each tool is versioned
independently, and only the tools changed in the current commit are released.

### One-time setup

- [ ] `gh auth login` (scope `repo`)
- [ ] `jq`, `git`, `bun` installed
- [ ] create the labels `new-tool`, `release:patch`, `release:minor`, `release:major`
- [ ] Settings → Pages → Source = "GitHub Actions"
- [ ] optional Packagist auto-register: export `PACKAGIST_USERNAME` / `PACKAGIST_TOKEN` and install the Packagist GitHub app

### Cut a release

After merging, on an up-to-date `main` (HEAD is the merge commit carrying the `release:*` label):

```bash
composer publish    # = mirrors:create → split → release
```

- `mirrors:create`: creates `toolkit-<tool>` for any new tool (skips existing). For a new mirror, also uncheck
  Settings → Features → **Pull requests** once (no API for it; the script reminds you).
- `split`: `git subtree split` extracts each tool folder (history intact) and force-pushes it to its mirror.
- `release`: for each tool changed in HEAD, bumps the mirror's latest tag per the label and cuts a GitHub Release;
  Packagist ships it from the tag.

The `release` step only tags the tools changed in HEAD. For the first release, or any time you need to tag
everything, run `composer release -- --all`. To tag specific tools, run `composer release -- Calculator Database`.

You may run the steps one at a time if you need to stop between them. Build the documentation site with
`composer docs:build` (output in `docs/.vitepress/dist`) and deploy it to your host of choice.

### Version bump

The bump comes from the merged PR's `release:*` label (read off HEAD's PR):

| Label | Effect |
|---|---|
| `new-tool` | new `src/<Tool>/`; creates the mirror and tags it `1.0.0` |
| `release:patch` / `release:minor` / `release:major` | bumps that part of every tool changed in the commit |

No label defaults to `patch`; a tool with no existing tag is released as `1.0.0`. One commit carries one bump type.

### Notes

- **Rerun-safe:** `release` stamps each release with the source commit and skips tools already released for it, so
  running it twice never double-bumps. Release an older commit with `GITHUB_SHA=<sha> composer release`.
- **No `version` field** in tool packages: versions are git tags on the mirrors. The root pins a static `version`
  only to silence Composer's notice; the root is never published.
- **Docs base path:** for `shipfastlabs.github.io/toolkit/` set `base: '/toolkit/'` in
  `docs/.vitepress/config.mts`; for a custom domain leave it and add a `CNAME`.

## Guidelines

- Send a coherent commit history; each commit should be meaningful.
- We follow [SemVer](https://semver.org/); one PR carries one bump type for every tool it changes.
- `dev` dependencies live in the **root** `composer.json` only; per-tool `composer.json` files carry no `require-dev`.
