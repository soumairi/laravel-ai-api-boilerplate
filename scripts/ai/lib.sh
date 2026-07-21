#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

readonly AI_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
readonly CANONICAL_TRACE_RE='^AI-[0-9]{8}-[0-9]{6}-[0-9A-F]{8}$'
readonly LEGACY_TRACE_RE='^[0-9]{8}T[0-9]{6}Z-[0-9a-f]{8}$'

die() { printf 'error: %s\n' "$*" >&2; exit 1; }
note() { printf '%s\n' "$*"; }

require_command() {
    command -v "$1" >/dev/null 2>&1 || die "required command not found: $1"
}

git_branch() {
    git -C "$AI_DIR" symbolic-ref --quiet --short HEAD || die 'detached HEAD is not allowed'
}

is_protected_branch() {
    case "$1" in
        main|master|develop|production|release/*) return 0 ;;
        *) return 1 ;;
    esac
}

active_trace_id() {
    local branch id
    branch="$(git_branch)"
    [[ "$branch" == ai/*/*/* ]] || die "branch must match ai/<trace-id>/<type>/<slug>: $branch"
    id="${branch#ai/}"
    printf '%s\n' "${id%%/*}"
}

active_trace_file() {
    local id file
    id="$(active_trace_id)"
    if [[ "$id" =~ $CANONICAL_TRACE_RE ]]; then
        file="$AI_DIR/ai/traces/${id:3:4}/${id:7:2}/$id.md"
    elif [[ "$id" =~ $LEGACY_TRACE_RE ]]; then
        file="$(awk -v legacy="$id" '
            $1 == "-" && $2 == "canonical:" { candidate=$3 }
            $1 == "legacy:" && $2 == legacy { matched=1 }
            matched && $1 == "file:" { print $2; exit }
        ' "$AI_DIR/ai/trace-map.yml")"
        [[ -n "$file" ]] || die "legacy trace is not mapped: $id"
        file="$AI_DIR/$file"
    else
        die "invalid trace ID in branch: $id"
    fi
    [[ -f "$file" ]] || die "active trace file not found: ${file#"$AI_DIR/"}"
    printf '%s\n' "$file"
}

canonical_trace_id() {
    local id
    id="$(active_trace_id)"
    if [[ "$id" =~ $CANONICAL_TRACE_RE ]]; then
        printf '%s\n' "$id"
        return
    fi

    awk -v legacy="$id" '
        $1 == "-" && $2 == "canonical:" { candidate=$3 }
        $1 == "legacy:" && $2 == legacy { print candidate; exit }
    ' "$AI_DIR/ai/trace-map.yml"
}

guard_branch() {
    local branch id
    branch="$(git_branch)"
    is_protected_branch "$branch" && die "protected branch refused: $branch"
    id="$(active_trace_id)"
    [[ "$id" =~ $CANONICAL_TRACE_RE || "$id" =~ $LEGACY_TRACE_RE ]] ||
        die "invalid AI trace branch: $branch"
}

contains_forbidden_path() {
    case "$1" in
        .env|.env.*|*/.env|*/.env.*) [[ "$1" == ".env.example" ]] || return 0 ;;
        *.pem|*.key|auth.json|storage/logs/*|vendor/*)
            [[ "$1" == "storage/logs/.gitignore" ]] || return 0
            ;;
    esac
    return 1
}
