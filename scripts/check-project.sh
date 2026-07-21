#!/usr/bin/env sh
set -eu

check="${1:-all}"
root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$root"

fail()
{
    printf 'check-project: %s\n' "$*" >&2
    exit 1
}

check_docs()
{
    if [ ! -d docs ]; then
        printf '%s\n' 'docs-check: skipped (docs/ not present yet)'
        return
    fi

    broken=0
    for file in $(find docs -type f -name '*.md' -print); do
        [ -s "$file" ] || { printf 'Empty documentation file: %s\n' "$file" >&2; broken=1; }
    done
    [ "$broken" -eq 0 ] || fail 'documentation validation failed'
}

check_readme()
{
    [ -f README.md ] || { printf '%s\n' 'readme-check: skipped (README.md not present yet)'; return; }
    [ -s README.md ] || fail 'README.md is empty'
    grep -q 'make ' README.md || fail 'README.md must document Make targets'
}

check_security()
{
    for file in $(find . -path './.git' -prune -o -type f \
        \( -name '*.pem' -o -name '*.key' -o -name '.env' \) -print); do
        git check-ignore -q "$file" && continue
        fail "non-ignored private key or environment file found: $file"
    done

    if command -v composer >/dev/null 2>&1; then
        composer audit --locked --no-interaction
    else
        printf '%s\n' 'security-check: composer audit skipped (composer unavailable)'
    fi
}

check_traces()
{
    [ -d ai/traces ] || { printf '%s\n' 'trace-check: skipped (ai/traces not present yet)'; return; }
    found=0
    for file in $(find ai/traces -type f -name 'AI-*.md' -print); do
        [ -f "$file" ] || continue
        found=1
        grep -q 'Trace ID:' "$file" || fail "$file has no Trace ID"
        grep -q 'Sanitization:' "$file" || fail "$file has no sanitization declaration"
        if grep -Eiq '(BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|AKIA[0-9A-Z]{16}|gh[pousr]_[A-Za-z0-9_]{20,})' "$file"; then
            fail "$file contains secret-like material"
        fi
    done
    [ "$found" -eq 1 ] || fail 'no trace markdown files found'
    [ -s ai/traces/index.jsonl ] || fail 'trace index is missing or empty'
}

check_rules()
{
    [ -d .cursor/rules ] || { printf '%s\n' 'rules-check: skipped (.cursor/rules not present yet)'; return; }
    find .cursor/rules -type f \( -name '*.mdc' -o -name 'RULE.md' \) -print | grep -q . \
        || fail 'no Cursor rule files found'
}

check_skills()
{
    [ -d .agents/skills ] || { printf '%s\n' 'skills-check: skipped (.agents/skills not present yet)'; return; }
    find .agents/skills -mindepth 2 -maxdepth 2 -name SKILL.md -print | grep -q . \
        || fail 'no canonical SKILL.md files found'
}

case "$check" in
    docs) check_docs ;;
    readme) check_readme ;;
    security) check_security ;;
    traces) check_traces ;;
    rules) check_rules ;;
    skills) check_skills ;;
    all)
        check_docs
        check_readme
        check_traces
        check_rules
        check_skills
        ;;
    *) fail "unknown check: $check" ;;
esac
