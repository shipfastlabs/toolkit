<p align="center">
    <p align="center">
        <a href="https://github.com/shipfastlabs/toolkit/actions"><img alt="GitHub Workflow Status (main)" src="https://github.com/shipfastlabs/toolkit/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://github.com/shipfastlabs/toolkit"><img alt="License" src="https://img.shields.io/github/license/shipfastlabs/toolkit"></a>
    </p>
</p>


# shipfastlabs/toolkit

Reusable AI tools for the [Laravel AI SDK](https://github.com/laravel/ai). One monorepo, developed together, that
subtree-splits into one tiny installable package per tool.

> Requires PHP 8.4+ and `laravel/ai`.

Usage and per-tool docs: **[toolkit.shipfastlabs.com](https://toolkit.shipfastlabs.com)**. This README is for
maintaining the repo.

## Layout

```
shipfastlabs/toolkit          ← dev monorepo (not installed directly)
├── stub/                     → template; copy to start a tool (never split)
├── tools/                    → publish scripts (split, release, mirrors, docs)
└── src/
    ├── Calculator/           → splits to shipfastlabs/toolkit-calculator
    └── */                    → splits to shipfastlabs/toolkit-*
```

Each `src/<Tool>/` is split into its own **read-only mirror** repo and published to Packagist, requiring only
`laravel/ai`. You develop and test everything here; mirrors are generated, never edited.

## Develop

```bash
composer install
composer test     # pint + rector + phpstan + pest, 100% type & code coverage
```

The root `composer.json` autoload maps every tool, so one install runs the whole suite. CI (`tests.yml`) runs the
same on push/PR.

## Releasing

Publishing runs **locally**: no CI secrets, no Actions to babysit. Each tool is versioned independently, and only
tools changed in the current commit are released.

**One-time setup:**

- [ ] `gh auth login` (scope `repo`)
- [ ] `jq`, `git`, `bun` installed
- [ ] create the labels `new-tool`, `release:patch`, `release:minor`, `release:major`
- [ ] Settings → Pages → Source = "GitHub Actions"
- [ ] optional Packagist auto-register: export `PACKAGIST_USERNAME` / `PACKAGIST_TOKEN` and install the Packagist GitHub app

### Add a tool

```bash
cp -R stub src/YourTool                     # rename class, namespace, composer.json package
# add YourTool to the root composer.json autoload + autoload-dev psr-4 blocks
composer dump-autoload
composer docgen -- YourTool                 # generate its README
composer test                               # until green
```

Open a PR, get it reviewed, label it `new-tool`, merge.

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

Run them one at a time if you need to stop between steps. Docs deploy on their own: pushing to `main` triggers
`docs.yml` (build VitePress → GitHub Pages); `composer docs:build` builds locally.

### Version bump

The bump comes from the merged PR's `release:*` label (read off HEAD's PR):

| Label | Effect |
|---|---|
| `new-tool` | new `src/<Tool>/`; creates the mirror and tags it `1.0.0` |
| `release:patch` / `release:minor` / `release:major` | bumps that part of every tool changed in the commit |

No label defaults to `patch`; a tool with no existing tag is released as `1.0.0`. One commit = one bump type.

### Good to know

- **Rerun-safe:** `release` stamps each release with the source commit and skips tools already released for it, so
  running it twice never double-bumps. Release an older commit with `GITHUB_SHA=<sha> composer release`.
- **No `version` field** in tool packages: versions are git tags on the mirrors. The root pins a static `version`
  only to silence Composer's notice; the root is never published.
- **Docs base path:** for `shipfastlabs.github.io/toolkit/` set `base: '/toolkit/'` in
  `docs/.vitepress/config.mts`; for a custom domain leave it and add a `CNAME`.

See [CONTRIBUTING.md](CONTRIBUTING.md) for the contributor flow.

By [Shipfastlabs](https://shipfastlabs.com), [MIT licensed](https://opensource.org/licenses/MIT).
