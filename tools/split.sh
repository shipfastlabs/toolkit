#!/usr/bin/env bash
#
# split.sh — subtree-split every tool folder under src/ into its read-only
# mirror repository, preserving history, using splitsh-lite.
#
# Auto-discovers folders (no matrix), so adding a tool never means editing this
# script. Folder -> repo name can be overridden in split-overrides.json when the
# package slug differs from the folder name.
#
# Required environment:
#   MIRROR_TOKEN  PAT with `repo` scope for pushing to the mirrors.
#
# Requires: git, splitsh-lite (https://github.com/splitsh/lite), jq.

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# splitsh-lite's --prefix is relative to the repo root.
cd "$ROOT"

if ! command -v splitsh-lite >/dev/null 2>&1; then
    echo "::error::splitsh-lite is not installed." >&2
    exit 1
fi

if [[ -z "${MIRROR_TOKEN:-}" ]]; then
    echo "::error::MIRROR_TOKEN is required for pushing to the mirrors." >&2
    exit 1
fi

split_folder() {
    local folder="$1"
    local repo
    repo="$(repo_name_for "$folder")"
    local remote="https://${MIRROR_TOKEN}@github.com/${ORG}/${repo}.git"

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
