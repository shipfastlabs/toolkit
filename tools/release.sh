#!/usr/bin/env bash
#
# release.sh — tag and release every tool folder changed in a commit, bumping
# each mirror's version independently per the PR's release:* label.
#
# Idempotent per source commit: each release body is stamped with the monorepo
# SHA, and a folder that already has a release for this SHA is skipped, so reruns
# never over-bump.
#
# Required environment:
#   GH_TOKEN            Token for the gh CLI (PAT with repo scope on the mirrors).
#   GITHUB_SHA          The commit to release (default: HEAD).
#   GITHUB_REPOSITORY   The monorepo, owner/name (default: <org>/toolkit).
#
# Requires: gh, jq, git.

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

require_gh

SHA="${GITHUB_SHA:-$(git rev-parse HEAD)}"
MONOREPO="${GITHUB_REPOSITORY:-${ORG}/toolkit}"

# Read the release:* bump and new-tool flag from the commit's merged PR.
labels="$(gh api "repos/${MONOREPO}/commits/${SHA}/pulls" \
    -H "Accept: application/vnd.github+json" \
    --jq '.[0].labels[].name' 2>/dev/null || true)"

bump="patch"
for candidate in major minor patch; do
    if grep -qx "release:${candidate}" <<<"$labels"; then
        bump="$candidate"
        break
    fi
done

is_new=false
if grep -qx "new-tool" <<<"$labels"; then
    is_new=true
fi

# The tool folders touched by this commit (excludes the stub template).
mapfile -t changed < <(
    git diff-tree --no-commit-id --name-only -r "$SHA" \
        | sed -n 's#^src/\([^/]*\)/.*#\1#p' \
        | grep -vx stub \
        | sort -u
)

if [[ ${#changed[@]} -eq 0 ]]; then
    echo "No tool folders changed in ${SHA}; nothing to release."
    exit 0
fi

bump_version() {
    local version="${1#v}" type="$2"
    local major minor patch
    IFS='.' read -r major minor patch <<<"$version"
    major="${major:-0}"
    minor="${minor:-0}"
    patch="${patch:-0}"

    case "$type" in
        major) echo "$((major + 1)).0.0" ;;
        minor) echo "${major}.$((minor + 1)).0" ;;
        *) echo "${major}.${minor}.$((patch + 1))" ;;
    esac
}

already_released_for() {
    local repo="$1" sha="$2"
    gh api "repos/${ORG}/${repo}/releases?per_page=100" --jq '.[].body' 2>/dev/null \
        | grep -qF "toolkit-source: ${sha}"
}

latest_tag() {
    local repo="$1"
    gh api "repos/${ORG}/${repo}/tags?per_page=1" --jq '.[0].name // empty' 2>/dev/null || true
}

for folder in "${changed[@]}"; do
    repo="$(repo_name_for "$folder")"

    if already_released_for "$repo" "$SHA"; then
        echo "==> ${ORG}/${repo} already released for ${SHA}; skipping (rerun-safe)."
        continue
    fi

    latest="$(latest_tag "$repo")"

    if [[ "$is_new" == true || -z "$latest" ]]; then
        next="1.0.0"
    else
        next="$(bump_version "$latest" "$bump")"
    fi

    echo "==> Releasing ${ORG}/${repo} ${latest:-<none>} -> ${next} (${bump})"

    gh api -X POST "repos/${ORG}/${repo}/releases" \
        -f tag_name="$next" \
        -f name="$next" \
        -f body="toolkit-source: ${SHA}" \
        -F generate_release_notes=true \
        -f target_commitish=main >/dev/null
done
