#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

ACTION="${1:-check}"
OPERATION="${2:-}"
case "$OPERATION" in commit|push|pr) ;; *) die "operation must be commit, push, or pr" ;; esac
file="$(active_trace_file)"
trace_path="${file#"$AI_DIR/"}"

approval_fingerprint() {
    local branch remote remote_url
    branch="$(git_branch)"
    if [[ "$OPERATION" == commit ]]; then
        {
            printf 'branch=%s\n' "$branch"
            git -C "$AI_DIR" rev-parse HEAD
            git -C "$AI_DIR" diff --cached --binary -- . ":(exclude)$trace_path"
        } | git -C "$AI_DIR" hash-object --stdin
        return
    fi

    remote="${AI_APPROVAL_REMOTE:-origin}"
    remote_url="$(git -C "$AI_DIR" remote get-url "$remote" 2>/dev/null || printf 'unconfigured')"
    {
        printf 'branch=%s\nremote=%s\nremote_url=%s\n' "$branch" "$remote" "$remote_url"
        git -C "$AI_DIR" rev-parse HEAD
        if [[ "$OPERATION" == pr ]]; then
            printf 'base=%s\n' "${AI_APPROVAL_BASE:-develop}"
        fi
    } | git -C "$AI_DIR" hash-object --stdin
}

fingerprint="$(approval_fingerprint)"

approval_exists() {
    awk -v prefix="- ${OPERATION}: approved " -v binding="for \`$fingerprint\`" '
        index($0, prefix) == 1 && index($0, binding) > 0 { found=1 }
        END { exit !found }
    ' "$file"
}

if [[ "$ACTION" == check ]]; then
    approval_exists ||
        die "$OPERATION approval is missing or does not match the current content"
    note "$OPERATION approval matches $fingerprint"
elif [[ "$ACTION" == record ]]; then
    [[ "${AI_HUMAN_APPROVAL:-}" == yes ]] ||
        die 'set AI_HUMAN_APPROVAL=yes only after explicit human approval'
    actor="${3:-human}"
    [[ "$actor" =~ ^[A-Za-z0-9._@-]+$ ]] || die 'invalid approver label'
    approval_exists && die "$OPERATION is already approved for the current content"
    printf '\n- %s: approved by `%s` at `%s` for `%s`\n' \
        "$OPERATION" "$actor" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$fingerprint" |
        "$AI_DIR/scripts/ai/trace.sh" append
    note "$OPERATION approval recorded for $fingerprint"
else
    die "usage: $0 {check|record} {commit|push|pr} [approver]"
fi
