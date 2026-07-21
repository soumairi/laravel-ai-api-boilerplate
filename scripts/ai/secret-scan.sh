#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"

exec "$ROOT/scripts/ai/security-check.sh" "${1:-worktree}"
