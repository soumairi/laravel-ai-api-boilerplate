#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

required=(
    AGENTS.md
    CLAUDE.md
    .cursor/rules/00-project-core.mdc
    .cursor/rules/10-laravel-architecture.mdc
    .cursor/rules/20-api-conventions.mdc
    .cursor/rules/30-jwt-authentication.mdc
    .cursor/rules/40-security.mdc
    .cursor/rules/50-database.mdc
    .cursor/rules/60-testing.mdc
    .cursor/rules/70-scramble-openapi.mdc
    .cursor/rules/80-ai-traceability.mdc
    .cursor/rules/90-git-workflow.mdc
)

for path in "${required[@]}"; do
    [[ -s "$AI_DIR/$path" ]] || die "required AI rule is missing or empty: $path"
done

note 'AI rules are present'
