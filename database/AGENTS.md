# Database changes

- PostgreSQL is authoritative; do not add SQLite-specific behavior.
- Migrations must be deterministic, reversible when safe, ULID-compatible, and explicit about indexes and foreign keys.
- Never add real credentials or personal data to migrations, factories, or seeders.
- Keep seeders idempotent and preserve RBAC/system-resource invariants.
- Validate with a fresh testing database before delivery; destructive commands require `APP_ENV=testing`.
