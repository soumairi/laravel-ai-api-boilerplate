#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"

"$ROOT/scripts/ai/validate.sh" --delivery
"$ROOT/scripts/ai/trace.sh" finish
echo 'AI work finalized locally; commit, push, and PR remain separately approval-gated.'
