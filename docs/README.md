# Docs site

The documentation website for `shipfastlabs/toolkit`, built with [VitePress](https://vitepress.dev) and deployed to
Vercel.

## Source of truth

- `guide/`: hand-written framing pages (getting started, contributing).
- `tools/`: **auto-generated**, do not edit. `php tools/sync-docs.php` (run from the repo root) copies every
  `src/<Tool>/README.md` here on each push to `master`. Edit the tool's README instead.

## Develop & build

```bash
bun install
bun run docs:dev        # local preview on the printed port
bun run docs:build      # static build -> .vitepress/dist (deployed to Vercel)
bun run docs:preview    # preview the production build
```

CI on `master` runs `php tools/sync-docs.php` first to refresh `tools/`, then builds and deploys, so adding a tool
publishes its doc page automatically.
