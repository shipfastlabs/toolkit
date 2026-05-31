#!/usr/bin/env bash
#
# lib.sh — shared helpers for the toolkit automation scripts.
#
# Source this from another script:
#   source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
#
# Resolves the repo root from this file's location (like PHP's __DIR__), so the
# scripts work regardless of the current working directory.

LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${LIB_DIR}/.." && pwd)"

ORG="shipfastlabs"
SRC_DIR="${ROOT}/src"
OVERRIDES_FILE="${ROOT}/split-overrides.json"
PACKAGE_PREFIX="toolkit-"

# Fail early unless the gh CLI is installed and authenticated.
# Accepts either a stored `gh auth login` session or a GH_TOKEN/GITHUB_TOKEN env var.
require_gh() {
    if ! command -v gh >/dev/null 2>&1; then
        echo "::error::gh CLI is not installed." >&2
        exit 1
    fi

    if ! gh auth status >/dev/null 2>&1; then
        echo "::error::gh CLI is not authenticated. Run 'gh auth login' or set GH_TOKEN." >&2
        exit 1
    fi
}

# Resolve the mirror repo name for a folder, honouring split-overrides.json.
repo_name_for() {
    local folder="$1" override=""

    if [[ -f "$OVERRIDES_FILE" ]]; then
        override="$(jq -r --arg f "$folder" '.[$f] // empty' "$OVERRIDES_FILE")"
    fi

    if [[ -n "$override" ]]; then
        echo "$override"
    else
        echo "${PACKAGE_PREFIX}$(echo "$folder" | tr '[:upper:]' '[:lower:]')"
    fi
}

# Print the basename of every tool folder under src/, excluding the stub template.
tool_folders() {
    local dir folder

    for dir in "${SRC_DIR}"/*/; do
        [[ -d "$dir" ]] || continue
        folder="$(basename "$dir")"
        [[ "$folder" == "stub" ]] && continue
        echo "$folder"
    done
}
