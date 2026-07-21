---
name: quality-gate
description: Execute the complete delivery validation gate.
---

# Quality gate

## Context and inputs
Use after focused checks pass and before any delivery write.

## Read
Read `AGENTS.md`, active plan/trace, Makefile, Composer scripts, CI, and changed files.

## Procedure
Run Composer validate/audit, Pint, PHPStan, protected migrations/seeding, focused and parallel Pest, 85% coverage, Scramble export/docs checks, caches/routes, security scan, governance validation, Compose/workflow checks, and diff review.

## Validation and output
Record exact commands, exit outcomes, coverage, skips, and blockers in the trace.

## Safety
Stop delivery on failure, never invent success, avoid duplicate full suites, and never expose environment values or keys.
