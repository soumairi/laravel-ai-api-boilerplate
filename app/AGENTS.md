# Application code

- Respect the dependency direction: HTTP → Application → Domain; Infrastructure implements external concerns.
- Keep controllers thin, requests strict, resources explicit, actions transactional, and domain rules framework-light.
- Authorize every object access and preserve last-super-admin, self-management, token-family, and system-resource protections.
- Use typed PHP 8.4 code, immutable DTOs where appropriate, ULIDs, and sanitized structured logs.
- Run Pint, PHPStan, and focused tests for touched behavior.
