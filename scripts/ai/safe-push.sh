#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

[[ $# -le 1 ]] || die 'usage: safe-push.sh [origin]'
remote="${1:-origin}"
[[ "$remote" == origin ]] || die "remote is not allowlisted: $remote"
guard_branch
branch="$(git_branch)"
git -C "$AI_DIR" rev-parse --verify HEAD >/dev/null 2>&1 || die 'cannot push an unborn branch'
[[ -z "$(git -C "$AI_DIR" status --porcelain)" ]] || die 'worktree must be clean before push'

url="$(git -C "$AI_DIR" remote get-url "$remote" 2>/dev/null)" || die "remote not configured: $remote"
case "$url" in
    git@github.com:*|https://github.com/*|ssh://git@github.com/*|git@gitlab.com:*|https://gitlab.com/*|ssh://git@gitlab.com/*) ;;
    *) die 'remote host or URL scheme is not allowlisted' ;;
esac

"$AI_DIR/scripts/ai/trace-check.sh"
"$AI_DIR/scripts/ai/approval.sh" check push
"$AI_DIR/scripts/ai/security-check.sh" worktree
"$AI_DIR/scripts/ai/validate.sh" --delivery
git -C "$AI_DIR" push --set-upstream "$remote" "HEAD:refs/heads/$branch"
note "pushed $branch without force"
