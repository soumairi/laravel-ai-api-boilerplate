#!/usr/bin/env bash
set -Eeuo pipefail
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)/lib.sh"

required=(
    docs/ai-agent-workflow.md
    docs/authorization.md
    docs/deployment.md
    docs/openapi-and-scramble.md
    docs/openapi/openapi.json
    docs/security.md
    docs/testing.md
)

for path in "${required[@]}"; do
    [[ -s "$AI_DIR/$path" ]] || die "required documentation is missing or empty: $path"
done

php -r '
$document = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
if (($document["openapi"] ?? null) !== "3.1.0" || empty($document["paths"])) {
    fwrite(STDERR, "OpenAPI document is incomplete\n");
    exit(1);
}
' "$AI_DIR/docs/openapi/openapi.json"

note 'documentation artifacts are valid'
