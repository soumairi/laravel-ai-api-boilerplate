#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

ACTION="${1:-}"
case "$ACTION" in
    new)
        purpose="${2:-}"
        [[ -n "$purpose" ]] || die "usage: $0 new <purpose>"
        suffix="$(openssl rand -hex 4 | tr '[:lower:]' '[:upper:]')"
        id="AI-$(date -u +%Y%m%d-%H%M%S)-$suffix"
        directory="$AI_DIR/ai/traces/${id:3:4}/${id:7:2}"
        file="$directory/$id.md"
        umask 077
        mkdir -p "$directory"
        (set -o noclobber; cat >"$file" <<EOF
# AI Trace

- Trace ID: \`$id\`
- Purpose: $purpose
- Sanitization: Secrets and sensitive values are excluded.
- Status: active

## Approvals

- commit: pending
- push: pending
- pr: pending

## Commands and results
EOF
        ) || die "trace already exists: $id"
        printf '{"id":"%s","path":"%s","status":"active","created_at":"%s"}\n' \
            "$id" "${file#"$AI_DIR/"}" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" >>"$AI_DIR/ai/traces/index.jsonl"
        printf '%s\n' "$id"
        ;;
    append)
        file="$(active_trace_file)"
        payload="$(cat)"
        [[ -n "$payload" ]] || die 'append requires sanitized text on stdin'
        if printf '%s' "$payload" | grep -Eiq '(-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----|authorization:[[:space:]]*(bearer|basic)|password[[:space:]]*[:=][[:space:]]*[^<[:space:]]|api[_-]?key[[:space:]]*[:=])'; then
            die 'trace text resembles secret material'
        fi
        lock="$file.lock"
        mkdir "$lock" 2>/dev/null || die 'trace is locked by another writer'
        trap 'rmdir "$lock" 2>/dev/null || true' EXIT
        printf '\n%s\n' "$payload" >>"$file"
        ;;
    finish)
        file="$(active_trace_file)"
        "$AI_DIR/scripts/ai/validate.sh"
        lock="$file.lock"
        mkdir "$lock" 2>/dev/null || die 'trace is locked by another writer'
        trap 'rmdir "$lock" 2>/dev/null || true' EXIT
        printf '\n## Finish\n\n- Finished at: `%s`\n- Status: validation passed; delivery remains approval-gated.\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" >>"$file"
        ;;
    *)
        die "usage: $0 {new <purpose>|append|finish}"
        ;;
esac
