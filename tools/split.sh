#!/usr/bin/env bash
#
# split.sh — subtree-split every tool folder under src/ into its read-only
# mirror repository, preserving history, using splitsh-lite.
#
# Auto-discovers folders (no matrix), so adding a tool never means editing this
# script. Folder -> repo name can be overridden in split-overrides.json when the
# package slug differs from the folder name.
#
# Authentication (first one set wins):
#   MIRROR_TOKEN  PAT with `repo` scope for pushing to the mirrors (CI), or
#   gh auth       falls back to `gh auth token` for local runs (no PAT needed).
#
# Requires: git, splitsh-lite (https://github.com/splitsh/lite), jq, gh (for the
#           local token fallback).

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# splitsh-lite's --prefix is relative to the repo root.
cd "$ROOT"

if ! command -v splitsh-lite >/dev/null 2>&1; then
    echo "::error::splitsh-lite is not installed." >&2
    exit 1
fi

TOKEN="${MIRROR_TOKEN:-$(gh auth token 2>/dev/null || true)}"

if [[ -z "$TOKEN" ]]; then
    echo "::error::No token for pushing. Set MIRROR_TOKEN or run 'gh auth login'." >&2
    exit 1
fi

split_folder() {
    local folder="$1"
    local repo
    repo="$(repo_name_for "$folder")"
    local remote="https://x-access-token:${TOKEN}@github.com/${ORG}/${repo}.git"

    echo "==> Splitting src/${folder} -> ${ORG}/${repo}"

    local sha
    sha="$(splitsh-lite --prefix="src/${folder}")"

    git push "$remote" "${sha}:refs/heads/main" --force
}

pids=()
while read -r folder; do
    split_folder "$folder" &
    pids+=($!)
done < <(tool_folders)

# Wait for every parallel push and fail if any of them did.
status=0
for pid in "${pids[@]}"; do
    wait "$pid" || status=1
done

exit "$status"
