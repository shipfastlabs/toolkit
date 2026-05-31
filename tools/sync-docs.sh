#!/usr/bin/env bash
#
# sync-docs.sh — copy each src/<Tool>/README.md into docs/tools/<tool>.md,
# stripping the Packagist badges and the AUTO-GENERATED marker and normalising
# the first heading to the tool's title. The tool README is the source of truth;
# docs/tools/ is generated, never edited by hand.
#
# Requires: awk.

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

target="${ROOT}/docs/tools"
mkdir -p "$target"

while read -r folder; do
    readme="${SRC_DIR}/${folder}/README.md"
    slug="$(echo "$folder" | tr '[:upper:]' '[:lower:]')"
    title="$(tr '[:lower:]' '[:upper:]' <<<"${slug:0:1}")${slug:1}"

    if [[ ! -f "$readme" ]]; then
        echo "! ${folder} has no README.md, skipping"
        continue
    fi

    destination="${target}/${slug}.md"

    # Drop badge and marker lines, retitle the first H1, trim leading/trailing
    # blank lines and collapse runs of blank lines to a single blank line.
    awk -v title="$title" '
        /^\[!\[/ { next }
        /^<!-- AUTO-GENERATED.*-->[[:space:]]*$/ { next }
        {
            line = $0
            if (!retitled && line ~ /^#[[:space:]]+.+/) { line = "# " title; retitled = 1 }
            if (line ~ /^[[:space:]]*$/) {
                if (started) pending = 1
                next
            }
            if (started && pending) print ""
            print line
            started = 1
            pending = 0
        }
    ' "$readme" >"$destination"

    echo "✓ ${destination}"
done < <(tool_folders)
