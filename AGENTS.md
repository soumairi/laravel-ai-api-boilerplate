# AI contributor guide

## Source of truth

- Follow this file, then the nearest nested `AGENTS.md`.
- Treat `.agents/skills/` as canonical. Run `scripts/ai/skill-sync.sh`; never edit synchronized skill copies.
- Use `ai/context-map.yml` to load only the context needed for the task.
- Keep one sanitized trace per AI branch. New IDs use `AI-YYYYMMDD-HHMMSS-XXXXXXXX`.

## Architecture

- Keep business rules in `app/Domain`, orchestration in `app/Application`, external adapters in `app/Infrastructure`, and HTTP concerns in `app/Http`.
- Preserve API-only behavior, versioned routes, strict validation, RFC 9457 errors, ULIDs, PostgreSQL, Redis, and existing auth/RBAC invariants.
- Never log credentials, tokens, cookies, authorization headers, private keys, environment values, or personal data.

## Workflow

1. Read the active plan, trace, this guide, and the nearest local guide.
2. Make the smallest scoped change; do not overwrite concurrent work.
3. Run targeted checks, then `scripts/ai/validate.sh`.
4. Append commands and truthful results to the trace.
5. Use safe Git scripts only after explicit approvals are recorded.

## Safety

- Never read or commit `.env*` except `.env.example`.
- Never commit generated secrets, key material, logs, caches, dependencies, or credentials.
- Never force-push or push protected branches (`main`, `master`, `develop`, `production`, `release/*`).
- Do not weaken tests or security controls to make a check pass.
