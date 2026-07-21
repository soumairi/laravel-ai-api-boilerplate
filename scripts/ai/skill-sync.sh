#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
SOURCE="$ROOT/.agents/skills"
TARGETS=("$ROOT/.cursor/skills" "$ROOT/.claude/skills" "$ROOT/.codex/skills")
MODE="${1:-sync}"
[[ "$MODE" == sync || "$MODE" == --check ]] || { echo "usage: $0 [--check]" >&2; exit 2; }
[[ -d "$SOURCE" ]] || { echo 'error: canonical skills directory missing' >&2; exit 1; }

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
cp -R "$SOURCE/." "$tmp/"

validate() {
    local file name dir
    while IFS= read -r -d '' file; do
        dir="$(basename "$(dirname "$file")")"
        name="$(awk -F': ' '$1 == "name" { print $2; exit }' "$file")"
        [[ "$(sed -n '1p' "$file")" == '---' ]] || return 1
        [[ "$name" == "$dir" ]] || return 1
        grep -q '^description: .\+' "$file" || return 1
    done < <(find "$SOURCE" -mindepth 2 -maxdepth 2 -name SKILL.md -print0 | LC_ALL=C sort -z)
}
validate || { echo 'error: invalid canonical SKILL.md metadata' >&2; exit 1; }

for target in "${TARGETS[@]}"; do
    if [[ "$MODE" == --check ]]; then
        [[ -d "$target" ]] && diff -qr "$tmp" "$target" >/dev/null ||
            { echo "error: skills out of sync: ${target#"$ROOT/"}" >&2; exit 1; }
    else
        mkdir -p "$target"
        find "$target" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
        cp -R "$tmp/." "$target/"
    fi
done

echo "skills ${MODE#--} complete"
