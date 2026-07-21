---
name: laravel-api
description: Use when changing the modular Laravel API or its security boundaries.
---

# Laravel API

- Follow HTTP → Application → Domain; keep Infrastructure behind contracts.
- Use strict Form Requests, explicit Resources, Policies, transactions, ULIDs, and typed PHP.
- Preserve JWT access-token, opaque refresh rotation/replay, RBAC, BOLA, and last-admin protections.
- Return stable success envelopes and RFC 9457 errors without infrastructure leakage.
- Sanitize logs and never expose tokens, credentials, private keys, or sensitive personal data.
