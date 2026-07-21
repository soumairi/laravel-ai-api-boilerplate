---
name: security-review
description: Review API changes for security defects.
---

# Security review

## Context and inputs
Use for an approved read-only review or security fix scope.

## Read
Read `AGENTS.md`, security/auth docs, changed code, routes, Policies, validation, migrations, logs, and security tests.

## Procedure
Check BOLA/IDOR, mass assignment, validation, permissions, secret leakage, JWT lifecycle, CORS, administrative protection, pagination/rate limits, uploads, and migration risk.

## Validation and output
Return defect-first findings with evidence; when fixes are authorized, add regression tests and run security checks.

## Safety
Do not expose exploit secrets, weaken controls, mutate external systems, or claim a scan that was not run.
