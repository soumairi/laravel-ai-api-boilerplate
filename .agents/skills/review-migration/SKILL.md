---
name: review-migration
description: Review PostgreSQL migrations for delivery risk.
---

# Review migration

## Context and inputs
Require the migration diff, production data assumptions, deployment order, and rollback expectations.

## Read
Read `AGENTS.md`, `database/AGENTS.md`, database conventions, related models, prior migrations, and persistence tests.

## Procedure
Review locks, destructive changes, defaults, nullability, constraints, indexes, ULIDs, foreign keys, data backfills, reversibility, and mixed-version deployment compatibility.

## Validation and output
Report actionable findings and run isolated testing migrations when fixes are authorized.

## Safety
Never execute destructive commands outside testing or rewrite an already-published migration.
