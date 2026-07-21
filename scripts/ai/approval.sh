#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

ACTION="${1:-check}"
OPERATION="${2:-}"
case "$OPERATION" in commit|push|pr) ;; *) die "operation must be commit, push, or pr" ;; esac
file="$(active_trace_file)"

if [[ "$ACTION" == check ]]; then
    grep -Eq "^- ${OPERATION}: approved( |$)" "$file" ||
        die "$OPERATION approval is not recorded in the active trace"
    note "$OPERATION approval recorded"
elif [[ "$ACTION" == record ]]; then
    [[ "${AI_HUMAN_APPROVAL:-}" == yes ]] ||
        die 'set AI_HUMAN_APPROVAL=yes only after explicit human approval'
    actor="${3:-human}"
    [[ "$actor" =~ ^[A-Za-z0-9._@-]+$ ]] || die 'invalid approver label'
    grep -Eq "^- ${OPERATION}: approved( |$)" "$file" && die "$OPERATION is already approved"
    printf '\n- %s: approved by `%s` at `%s`\n' \
        "$OPERATION" "$actor" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" |
        "$AI_DIR/scripts/ai/trace.sh" append
    note "$OPERATION approval recorded"
else
    die "usage: $0 {check|record} {commit|push|pr} [approver]"
fi
