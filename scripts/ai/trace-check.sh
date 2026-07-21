#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

guard_branch
id="$(active_trace_id)"
canonical="$(canonical_trace_id)"
file="$(active_trace_file)"

[[ "$canonical" =~ $CANONICAL_TRACE_RE ]] || die "canonical trace mapping is invalid: $canonical"
grep -Fq -- "- Trace ID: \`$canonical\`" "$file" ||
    die "trace metadata does not match canonical ID: $canonical"
grep -q '^## Commands and results' "$file" || die 'trace lacks commands/results section'
grep -q 'Sanitization:' "$file" || die 'trace lacks sanitization declaration'
if grep -Eiq '(-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----|authorization:[[:space:]]*(bearer|basic)|password[[:space:]]*[:=][[:space:]]*[^<[:space:]])' "$file"; then
    die 'trace may contain secret material'
fi

if [[ "$id" =~ $LEGACY_TRACE_RE ]]; then
    grep -Fq "legacy: $id" "$AI_DIR/ai/trace-map.yml" ||
        die 'legacy trace compatibility mapping missing'
fi
grep -Fq "\"id\":\"$canonical\"" "$AI_DIR/ai/traces/index.jsonl" ||
    die 'canonical trace index entry is missing'
grep -Fq "\"path\":\"${file#"$AI_DIR/"}\"" "$AI_DIR/ai/traces/index.jsonl" ||
    die 'canonical trace index path is missing'
note "trace valid: ${file#"$AI_DIR/"}"
