#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
PROFILE="${1:-governance}"
MODE="${2:-list}"
[[ "$MODE" == list || "$MODE" == --check ]] || { echo "usage: $0 [profile] [--check]" >&2; exit 2; }

python3 - "$ROOT" "$PROFILE" "$MODE" <<'PY'
from fnmatch import fnmatch
from pathlib import Path
import subprocess
import sys

root, profile, mode = Path(sys.argv[1]), sys.argv[2], sys.argv[3]
lines = (root / "ai/context-map.yml").read_text().splitlines()
profiles, exclusions, current = {}, [], None
section = None
for raw in lines:
    stripped = raw.strip()
    if not stripped or stripped.startswith("#"):
        continue
    if stripped == "profiles:":
        section = "profiles"
        continue
    if stripped == "exclusions:":
        section, current = "exclusions", None
        continue
    if section == "profiles" and raw.startswith("  ") and not raw.startswith("    ") and stripped.endswith(":"):
        current = stripped[:-1]
        profiles[current] = []
    elif stripped.startswith("- "):
        value = stripped[2:].strip("'\"")
        (exclusions if section == "exclusions" else profiles[current]).append(value)

if profile not in profiles:
    sys.exit(f"error: unknown context profile: {profile}")
if not profiles[profile] or not exclusions:
    sys.exit("error: context map must define includes and exclusions")
if mode == "--check":
    print("context map valid")
    raise SystemExit

result = subprocess.run(
    ["git", "-C", str(root), "ls-files", "--cached", "--others", "--exclude-standard", "-z"],
    check=True, capture_output=True,
)
paths = sorted(p for p in result.stdout.decode().split("\0") if p)
for path in paths:
    included = any(fnmatch(path, p) or (p.endswith("/**") and path.startswith(p[:-3] + "/")) for p in profiles[profile])
    excluded = any(fnmatch(path, p) or (p.endswith("/**") and path.startswith(p[:-3] + "/")) for p in exclusions)
    if included and not excluded:
        print(path)
PY
