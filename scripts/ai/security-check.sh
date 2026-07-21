#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

MODE="${1:-worktree}"
files=()
case "$MODE" in
    staged)
        while IFS= read -r -d '' file; do files+=("$file"); done \
            < <(git -C "$AI_DIR" diff --cached --name-only --diff-filter=ACMR -z)
        ;;
    worktree)
        while IFS= read -r -d '' file; do files+=("$file"); done \
            < <(git -C "$AI_DIR" ls-files --cached --others --exclude-standard -z)
        ;;
    *) die "usage: $0 [worktree|staged]" ;;
esac

((${#files[@]})) || die "no files available for $MODE security scan"
for file in "${files[@]}"; do
    contains_forbidden_path "$file" && die "forbidden sensitive path: $file"
done

python3 - "$AI_DIR" "${files[@]}" <<'PY'
from pathlib import Path
import re
import sys

root = Path(sys.argv[1])
assignment = re.compile(
    r"(?i)\b(password|passwd|secret|api[_-]?key|access[_-]?token|client[_-]?secret)"
    r"\s*[:=]\s*['\"]?([A-Za-z0-9/+_.=-]{12,})"
)
token = re.compile(r"\b(?:ghp_|github_pat_|glpat-|sk_live_)[A-Za-z0-9_-]{16,}\b")
private_header = "-----BEGIN " + "PRIVATE KEY-----"

for relative in sys.argv[2:]:
    path = root / relative
    if not path.is_file() or path.stat().st_size > 2_000_000:
        continue
    try:
        text = path.read_text(errors="strict")
    except (UnicodeDecodeError, OSError):
        continue
    for number, line in enumerate(text.splitlines(), 1):
        if "grep -Ei" in line or "re.compile" in line:
            continue
        match = assignment.search(line)
        value = match.group(2) if match else ""
        if private_header in line or token.search(line) or (match and not value.startswith(("env", "changeme", "example"))):
            print(f"error: possible secret in {relative}:{number}", file=sys.stderr)
            raise SystemExit(1)
print("security scan passed")
PY

if command -v gitleaks >/dev/null 2>&1; then
    gitleaks git --no-banner --redact --log-opts=-1 "$AI_DIR"
else
    note 'gitleaks unavailable; deterministic built-in scan completed'
fi
