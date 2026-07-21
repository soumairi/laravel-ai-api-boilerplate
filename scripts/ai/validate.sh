#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

MODE="${1:-governance}"
[[ "$MODE" == governance || "$MODE" == --delivery ]] ||
    die "usage: $0 [--delivery]"

while IFS= read -r -d '' script; do
    bash -n "$script"
done < <(find "$AI_DIR/scripts/ai" -type f -name '*.sh' -print0 | LC_ALL=C sort -z)

while IFS= read -r -d '' rule; do
    [[ "$(sed -n '1p' "$rule")" == '---' ]] || die "invalid Rule frontmatter: $rule"
    awk 'NR > 1 && $0 == "---" { found=1; exit } END { exit !found }' "$rule" ||
        die "unterminated Rule frontmatter: $rule"
    grep -Eq '^(description|alwaysApply): ' "$rule" ||
        die "incomplete Rule metadata: $rule"
done < <(find "$AI_DIR/.cursor/rules" -type f -name '*.mdc' -print0 | LC_ALL=C sort -z)

"$AI_DIR/scripts/ai/skill-sync.sh" --check
"$AI_DIR/scripts/ai/context.sh" governance --check
"$AI_DIR/scripts/ai/trace-check.sh"
"$AI_DIR/scripts/ai/security-check.sh" worktree

if [[ "$MODE" == --delivery ]]; then
    [[ -f "$AI_DIR/Makefile" ]] || die 'delivery blocked: Makefile is not implemented'
    make -C "$AI_DIR" --no-print-directory quality
fi

note "${MODE#--} validation passed"
