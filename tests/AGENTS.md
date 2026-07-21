# Tests

- Use Pest and test observable behavior, authorization boundaries, failure paths, and security invariants.
- Integration tests use isolated PostgreSQL and Redis; do not make SQLite the reference engine.
- Avoid timing-sensitive assertions, shared state, external network calls, and real secrets.
- Add regression coverage with every bug fix. Do not weaken or delete assertions merely to pass.
- Keep factories and helpers deterministic; redact tokens and credentials from failure output.
