---
name: write-tests
description: Add meaningful Pest regression coverage.
---

# Write tests

## Context and inputs
Require changed behavior, acceptance criteria, failure paths, and security boundaries.

## Read
Read `AGENTS.md`, `tests/AGENTS.md`, testing docs, implementation, helpers, and adjacent tests.

## Procedure
Cover happy path, validation, authentication, authorization, business errors, boundaries, concurrency, side effects, JSON contracts, and OpenAPI impact using deterministic PostgreSQL/Redis-backed Pest tests.

## Validation and output
Run focused tests then the relevant full/coverage gate; list scenarios and truthful outcomes.

## Safety
Do not use external services, real secrets, retries, sleeps, SQLite equivalence, or artificial coverage-only assertions.
