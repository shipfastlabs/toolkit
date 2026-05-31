#!/usr/bin/env bash
#
# split.sh — subtree-split every tool folder under src/ into its read-only
# mirror repository, preserving history, using splitsh-lite.
#
# Adapted from SocialiteProviders/Providers. Auto-discovers folders (no matrix),
# so adding a tool never means editing this script. Folder -> repo name can be
# overridden in split-overrides.json when the package slug differs from the
# folder name.
#
# Required environment:
#   GITHUB_PUSH_TOKEN  PAT with `repo` scope for pushing to the mirrors.
#   GITHUB_ORG         Target org/user (default: shipfastlabs).
#
# Requires: git, splitsh-lite (https://github.com/splitsh/lite), jq.

set -euo pipefail

ORG="${GITHUB_ORG:-shipfastlabs}"
SRC_DIR="src"
OVERRIDES_FILE="split-overrides.json"
PACKAGE_PREFIX="toolkit-"

if ! command -v splitsh-lite >/dev/null 2>&1; then
    echo "::error::splitsh-lite is not installed." >&2
    exit 1
fi

# Resolve the mirror repo name for a given folder, honouring split-overrides.json.
repo_name_for() {
    local folder="$1"
    local override=""

    if [[ -f "$OVERRIDES_FILE" ]]; then
        override="$(jq -r --arg f "$folder" '.[$f] // empty' "$OVERRIDES_FILE")"
    fi

    if [[ -n "$override" ]]; then
        echo "$override"
    else
        echo "${PACKAGE_PREFIX}$(echo "$folder" | tr '[:upper:]' '[:lower:]')"
    fi
}

split_folder() {
    local folder="$1"
    local repo
    repo="$(repo_name_for "$folder")"
    local remote="https://${GITHUB_PUSH_TOKEN}@github.com/${ORG}/${repo}.git"

    echo "==> Splitting ${SRC_DIR}/${folder} -> ${ORG}/${repo}"

    local sha
    sha="$(splitsh-lite --prefix="${SRC_DIR}/${folder}")"

    git push "$remote" "${sha}:refs/heads/master" --force
}

pids=()
for dir in "${SRC_DIR}"/*/; do
    folder="$(basename "$dir")"

    # The stub folder is a template, never a published package.
    if [[ "$folder" == "stub" ]]; then
        continue
    fi

    split_folder "$folder" &
    pids+=($!)
done

# Wait for every parallel push and fail if any of them did.
status=0
for pid in "${pids[@]}"; do
    wait "$pid" || status=1
done

exit "$status"
