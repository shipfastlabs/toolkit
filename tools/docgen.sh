#!/usr/bin/env bash
#
# docgen.sh — generate the per-tool README from a stub. Regenerates the header
# (title, badges, marker) and, for a new tool, scaffolds the authored sections;
# for an existing tool it preserves everything below the AUTO-GENERATED marker.
#
# Usage:
#   tools/docgen.sh            # every tool under src/
#   tools/docgen.sh Calculator # just one tool
#
# Requires: jq.

set -euo pipefail

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

only="${1:-}"
marker='<!-- AUTO-GENERATED: do not edit above this line. Run `tools/docgen.sh`. -->'

if [[ -n "$only" ]]; then
    folders=("${SRC_DIR}/${only}")
else
    folders=("${SRC_DIR}"/*/)
fi

for path in "${folders[@]}"; do
    path="${path%/}"
    folder="$(basename "$path")"
    composer="${path}/composer.json"

    [[ -f "$composer" ]] || continue

    package="$(jq -r '.name // empty' "$composer")"
    [[ -z "$package" ]] && package="${ORG}/toolkit-$(echo "$folder" | tr '[:upper:]' '[:lower:]')"

    description="$(jq -r '.description // empty' "$composer")"
    [[ -z "$description" ]] && description="The ${folder} tool for the Laravel AI SDK"

    namespace="$(jq -r '[(.autoload."psr-4" // {}) | to_entries[] | .key] | last // ""' "$composer")"
    namespace="${namespace%\\}"

    if [[ "$folder" == "stub" ]]; then
        short_class="StubTool"
    else
        short_class="${folder}Tool"
    fi

    if [[ -n "$namespace" ]]; then
        class="${namespace}\\${short_class}"
    else
        class="$short_class"
    fi

    readme="${path}/README.md"

    header="# ${package}

[![Latest Version](https://img.shields.io/packagist/v/${package}.svg)](https://packagist.org/packages/${package})
[![Total Downloads](https://img.shields.io/packagist/dt/${package}.svg)](https://packagist.org/packages/${package})

> ${description}

Part of the [${ORG}/toolkit](https://github.com/${ORG}/toolkit) catalog of reusable AI tools for the Laravel AI SDK.

${marker}"

    generated="## Installation

\`\`\`bash
composer require ${package}
\`\`\`

## Usage

Add the tool to an agent's \`tools()\`:

\`\`\`php
use ${class};

\$tools = [new ${short_class}];
\`\`\`

## Input schema

<!-- Document each schema parameter: name, type, whether required, and what it does. -->

## Configuration

<!-- If the tool needs config, read it from config('ai.toolkit.<tool>.<key>') and document the keys here for the user
     to add to their config/ai.php manually. Tools do NOT ship config files or service providers. -->
<!-- Remove this section entirely for pure tools that need no configuration. -->

## Safety

<!-- Note any guardrails: allow-lists, read-only enforcement, size caps, timeouts. -->"

    # Preserve everything below the existing marker. Match on the stable
    # "<!-- AUTO-GENERATED" prefix so the exact marker text can change without
    # wiping authored content.
    if [[ -f "$readme" ]] && grep -q '<!-- AUTO-GENERATED' "$readme"; then
        # Capture everything below the marker, then drop leading blank lines so a
        # single blank separator (added below) keeps the output idempotent.
        authored="$(awk 'found { print } /<!-- AUTO-GENERATED/ { found = 1 }' "$readme" | sed '/./,$!d')"
    else
        authored="$generated"
    fi

    printf '%s\n\n%s\n' "$header" "$authored" >"$readme"
    echo "✓ ${readme}"
done
