---
name: governance-workflow
description: Use for scoped AI work, context selection, traces, and approvals in this repository.
---

# Governance workflow

1. Read the approved plan, `AGENTS.md`, nearest local guide, and active trace.
2. Select one profile with `scripts/ai/context.sh <profile>`; avoid excluded files.
3. Change only approved scope and preserve concurrent edits.
4. Run targeted checks and `scripts/ai/validate.sh`.
5. Append sanitized commands and results with `scripts/ai/trace.sh append`.
6. Record explicit approvals before safe commit, push, or PR preparation.

Never claim a check ran when it did not.
