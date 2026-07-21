---
name: implement-jwt-auth
description: Change JWT and refresh-token security safely.
---

# Implement JWT auth

## Context and inputs
Require the authentication flow, threat model, token lifetime, and revocation behavior.

## Read
Read `AGENTS.md`, authentication/security docs, TokenService, refresh service, middleware, JWT config, and auth tests.

## Procedure
Keep RS256 access tokens behind the service boundary; use opaque hash-only refresh tokens, transactional rotation, Redis locks, replay-family revocation, and token-version invalidation.

## Validation and output
Test issue/validate/refresh/logout/revoke-all, invalid signatures, expiry, replay, and Redis behavior; report no token values.

## Safety
Never log or persist raw tokens, expose keys, weaken account-status checks, or use real credentials in tests.
