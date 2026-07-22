#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

[[ $# -le 1 ]] || die 'usage: prepare-pr.sh [base-branch]'
base="${1:-develop}"
[[ "$base" =~ ^[A-Za-z0-9._/-]+$ && "$base" != -* ]] || die 'invalid base branch'
guard_branch
branch="$(git_branch)"
[[ "$branch" != "$base" ]] || die 'head and base branches must differ'
git -C "$AI_DIR" rev-parse --verify HEAD >/dev/null 2>&1 || die 'cannot prepare a PR from an unborn branch'
[[ -z "$(git -C "$AI_DIR" status --porcelain)" ]] || die 'worktree must be clean before PR preparation'

"$AI_DIR/scripts/ai/trace-check.sh"
AI_APPROVAL_REMOTE=origin AI_APPROVAL_BASE="$base" "$AI_DIR/scripts/ai/approval.sh" check pr
"$AI_DIR/scripts/ai/security-check.sh" worktree
"$AI_DIR/scripts/ai/validate.sh" --delivery
trace="$(active_trace_id)"

cat <<EOF
## Summary

- Implements the approved scope on \`$branch\`.
- Trace: \`$trace\`.

## Validation

- \`scripts/ai/validate.sh --delivery\`

## Review safety

- No force push; protected branches and non-allowlisted remotes are refused.
- Secrets and sensitive paths are excluded and scanned.

Base: \`$base\`
EOF
