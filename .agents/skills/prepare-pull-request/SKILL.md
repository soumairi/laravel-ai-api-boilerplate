---
name: prepare-pull-request
description: Prepare an accurate reviewable pull request.
---

# Prepare pull request

## Context and inputs
Require base branch, validated committed changes, trace ID, and explicit PR approval.

## Read
Read `AGENTS.md`, active trace, policy, all branch commits/diff, tests, migrations, permissions, and OpenAPI changes.

## Procedure
Verify branch and remote safety, summarize purpose and all changes, include endpoints, migrations, permissions, tests, coverage, risks, and checklist, then use the approved PR workflow.

## Validation and output
Return the created PR URL or generated body and exact external blocker.

## Safety
Never create from a protected/dirty branch, omit failed checks, expose secrets, force-push, or merge automatically.
