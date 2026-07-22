#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

[[ $# -eq 1 && -n "$1" ]] || die "usage: $0 '<commit subject>'"
subject="$1"
[[ "$subject" != *$'\n'* && ${#subject} -le 72 ]] || die 'commit subject must be one line and at most 72 characters'
guard_branch
git -C "$AI_DIR" diff --cached --quiet && die 'nothing is staged'
git -C "$AI_DIR" diff --quiet || die 'unstaged changes are not allowed during validated commit'
[[ -z "$(git -C "$AI_DIR" ls-files --others --exclude-standard)" ]] ||
    die 'untracked files are not allowed during validated commit'
"$AI_DIR/scripts/ai/trace-check.sh"
"$AI_DIR/scripts/ai/approval.sh" check commit
"$AI_DIR/scripts/ai/security-check.sh" staged
"$AI_DIR/scripts/ai/validate.sh" --delivery

trace="$(active_trace_id)"
git -C "$AI_DIR" diff --quiet || die 'validation changed tracked files; review and re-approve'
[[ -z "$(git -C "$AI_DIR" ls-files --others --exclude-standard)" ]] ||
    die 'validation created untracked files; review and re-approve'
"$AI_DIR/scripts/ai/approval.sh" check commit
git -C "$AI_DIR" diff --cached --check
git -C "$AI_DIR" commit -m "$subject" -m "Trace-ID: $trace"
note "commit created with trace $trace"
