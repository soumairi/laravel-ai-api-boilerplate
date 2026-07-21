#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

required=(
    bootstrap-project create-module create-api-endpoint create-migration
    implement-jwt-auth write-tests sync-openapi security-review
    review-migration quality-gate trace-ai-task finalize-git-task
    prepare-pull-request governance-workflow laravel-api safe-git testing-quality
)

for skill in "${required[@]}"; do
    path="$AI_DIR/.agents/skills/$skill/SKILL.md"
    [[ -s "$path" ]] || die "required skill is missing: $skill"
    [[ "$(awk 'NR == 1 { print; exit }' "$path")" == '---' ]] || die "skill frontmatter is missing: $skill"
    rg -q '^name:[[:space:]]+' "$path" || die "skill name is missing: $skill"
    rg -q '^description:[[:space:]]+' "$path" || die "skill description is missing: $skill"
done

note 'canonical skills are valid'
