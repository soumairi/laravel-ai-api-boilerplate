---
name: safe-git
description: Use before commits, pushes, or pull-request preparation.
---

# Safe Git delivery

- Work only on `ai/<trace-id>/<type>/<slug>` branches.
- Stage intentionally and inspect the staged diff.
- Require a valid trace, explicit operation approval, passing validation, and security scan.
- Use `safe-commit.sh`, `safe-push.sh`, and `prepare-pr.sh`; do not bypass them.
- Never force-push, push a protected branch, use an unapproved remote/host, or include secrets.
- Stop on ambiguity or failure; append the truthful result to the trace.
