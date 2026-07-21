---
name: testing-quality
description: Use when adding tests or validating Laravel changes.
---

# Testing and quality

- Add focused regression tests for changed behavior and failure paths.
- Use Pest with isolated PostgreSQL and Redis; avoid SQLite equivalence assumptions.
- Keep tests deterministic, parallel-safe, network-free, and secret-free.
- Run focused tests first, then formatting, static analysis, required integration tests, and coverage gates.
- Report skipped or blocked checks exactly; never weaken assertions to pass.
