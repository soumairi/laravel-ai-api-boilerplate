---
name: create-migration
description: Create a safe PostgreSQL migration.
---

# Create migration

## Context and inputs
Require the approved schema change, constraints, indexes, data volume, and rollback expectations.

## Read
Read `AGENTS.md`, `database/AGENTS.md`, database conventions, current migrations, models, and integration tests.

## Procedure
Create a new deterministic migration using PostgreSQL semantics, ULIDs where public, explicit constraints and indexes, and a safe rollback.

## Validation and output
Run protected `migrate:fresh --seed` in testing and relevant persistence tests; report migration and rollback risks.

## Safety
Never edit a published migration or run destructive operations outside an isolated testing database.
