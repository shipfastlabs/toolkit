#!/usr/bin/env bash
#
# update-repo-descriptions.sh — keep every mirror's description, homepage and
# topics in sync with each tool's composer.json. Intended to run on a schedule.
#
# Required environment:
#   GH_TOKEN    Token for the gh CLI (PAT with repo scope on the mirrors).
#
# Requires: gh, jq.

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

require_gh

while read -r folder; do
    repo="$(repo_name_for "$folder")"
    composer="${SRC_DIR}/${folder}/composer.json"

    description="$(jq -r '.description // empty' "$composer")"
    [[ -z "$description" ]] && description="The ${folder} tool for the Laravel AI SDK"

    mapfile -t topics < <(
        {
            printf '%s\n' laravel ai laravel-ai tool
            jq -r '.keywords[]?' "$composer"
        } | tr '[:upper:]' '[:lower:]' | awk '!seen[$0]++'
    )

    echo "Updating ${ORG}/${repo}"

    gh api -X PATCH "repos/${ORG}/${repo}" \
        -f description="[READ ONLY] ${description}" \
        -f homepage="https://github.com/${ORG}/toolkit" >/dev/null

    topic_args=()
    for t in "${topics[@]}"; do
        topic_args+=(-f "names[]=${t}")
    done

    gh api -X PUT "repos/${ORG}/${repo}/topics" "${topic_args[@]}" >/dev/null
done < <(tool_folders)
