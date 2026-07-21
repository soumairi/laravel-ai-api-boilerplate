#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

required=(
    'make install'
    'make fresh'
    'make test'
    'make test-coverage'
    'make quality'
    'make docs'
    'make ai-task-start'
    'make git-safe-commit'
    'api.documentation.view'
)

for text in "${required[@]}"; do
    grep -Fq -- "$text" "$AI_DIR/README.md" || die "README is missing required reference: $text"
done

note 'README references are synchronized'
