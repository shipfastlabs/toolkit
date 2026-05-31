# Docs site

The documentation website for `shipfastlabs/toolkit`, built with [VitePress](https://vitepress.dev) and deployed to
GitHub Pages.

## Source of truth

- `guide/`: hand-written framing pages (getting started, contributing).
- `tools/`: **auto-generated**, do not edit. `tools/sync-docs.sh` copies every
  `src/<Tool>/README.md` here on each push to `master`. Edit the tool's README instead.

## Develop & build

```bash
bun install
bun run docs:dev        # local preview on the printed port
bun run docs:build      # static build -> .vitepress/dist (deployed to GitHub Pages)
bun run docs:preview    # preview the production build
```

CI on `master` runs `tools/sync-docs.sh` first to refresh `tools/`, then builds and deploys, so adding a tool
publishes its doc page automatically.
