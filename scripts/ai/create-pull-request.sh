#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"

exec "$ROOT/scripts/ai/prepare-pr.sh" "${1:-develop}"
