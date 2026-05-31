#!/usr/bin/env bash
#
# split.sh — subtree-split every tool folder under src/ into its read-only
# mirror repository, preserving history, using git's built-in `git subtree`.
#
# Auto-discovers folders, so adding a tool never means editing this script.
# Folder -> repo name can be overridden in split-overrides.json.
#
# Authentication (first one set wins):
#   MIRROR_TOKEN  PAT with `repo` scope for pushing to the mirrors (CI), or
#   gh auth       falls back to `gh auth token` for local runs (no PAT needed).
#
# Requires: git (ships with `git subtree`), jq, gh (for the local token fallback).

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# git subtree's --prefix is relative to the repo root.
cd "$ROOT"

TOKEN="${MIRROR_TOKEN:-$(gh auth token 2>/dev/null || true)}"

if [[ -z "$TOKEN" ]]; then
    echo "::error::No token for pushing. Set MIRROR_TOKEN or run 'gh auth login'." >&2
    exit 1
fi

# Sequential on purpose: git subtree shares a cache in .git and is not safe to
# run in parallel against the same repo.
status=0
while read -r folder; do
    repo="$(repo_name_for "$folder")"
    remote="https://x-access-token:${TOKEN}@github.com/${ORG}/${repo}.git"

    echo "==> Splitting src/${folder} -> ${ORG}/${repo}"

    if ! sha="$(git subtree split --prefix="src/${folder}")"; then
        echo "::error::split failed for src/${folder}" >&2
        status=1
        continue
    fi

    git push "$remote" "${sha}:refs/heads/main" --force || status=1
done < <(tool_folders)

exit "$status"
