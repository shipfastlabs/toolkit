#!/usr/bin/env bash
#
# create-repos.sh — create the read-only mirror repository for any new tool
# folder under src/ that does not yet have one, then register it on Packagist.
#
# Required environment:
#   GH_TOKEN            Token for the gh CLI (PAT with repo + org admin scope).
# Optional environment:
#   PACKAGIST_USERNAME  Packagist user for auto-registration.
#   PACKAGIST_TOKEN     Packagist API token for auto-registration.
#
# Requires: gh, jq, curl.

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

require_gh

register_on_packagist() {
    local url="$1"

    if [[ -z "${PACKAGIST_USERNAME:-}" || -z "${PACKAGIST_TOKEN:-}" ]]; then
        echo "  (skipping Packagist registration - credentials not set)"
        return 0
    fi

    local status
    status="$(curl -s -o /dev/null -w '%{http_code}' \
        -X POST "https://packagist.org/api/create-package?username=${PACKAGIST_USERNAME}&apiToken=${PACKAGIST_TOKEN}" \
        -H 'Content-Type: application/json' \
        -H 'User-Agent: shipfastlabs-toolkit' \
        -d "{\"repository\":{\"url\":\"${url}\"}}")"

    if [[ "$status" -lt 300 ]]; then
        echo "  ✓ Registered on Packagist"
    else
        echo "  ! Packagist registration returned HTTP ${status}"
    fi
}

while read -r folder; do
    repo="$(repo_name_for "$folder")"

    if gh api "repos/${ORG}/${repo}" >/dev/null 2>&1; then
        echo "✓ ${ORG}/${repo} already exists"
        continue
    fi

    echo "+ Creating ${ORG}/${repo}"

    gh repo create "${ORG}/${repo}" \
        --public \
        --description "[READ ONLY] Subtree split of the ${folder} tool - see github.com/${ORG}/toolkit" \
        --homepage "https://github.com/${ORG}/toolkit" \
        --disable-issues \
        --disable-wiki

    gh api -X PATCH "repos/${ORG}/${repo}" -F has_projects=false >/dev/null

    gh api -X PUT "repos/${ORG}/${repo}/topics" \
        -f "names[]=laravel" \
        -f "names[]=ai" \
        -f "names[]=tool" \
        -f "names[]=laravel-ai" >/dev/null

    # Pull requests can only be disabled in the UI (no REST API field as of 2026).
    echo "  ! Disable PRs manually: Settings > Features > uncheck Pull requests for ${ORG}/${repo}"

    register_on_packagist "https://github.com/${ORG}/${repo}"
done < <(tool_folders)
