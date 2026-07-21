#!/usr/bin/env bash
set -Eeuo pipefail

command_text="${*:-}"
[[ -n "$command_text" ]] || { echo 'usage: ai-guard.sh <command>' >&2; exit 2; }

if [[ "$command_text" =~ (^|[[:space:]])(rm[[:space:]]+-rf|git[[:space:]]+(reset[[:space:]]+--hard|clean[[:space:]]+-fdx|push[[:space:]]+(-f|--force))|cat[[:space:]]+\.env|printenv)([[:space:]]|$) ]]; then
    echo 'ai-guard: blocked unsafe command' >&2
    exit 1
fi

if [[ "$command_text" =~ php[[:space:]]+artisan[[:space:]]+(migrate:fresh|db:wipe) ]] &&
    [[ "${APP_ENV:-}" != testing ]]; then
    echo 'ai-guard: destructive database command requires APP_ENV=testing' >&2
    exit 1
fi

echo 'ai-guard: command accepted'
