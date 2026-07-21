---
name: finalize-git-task
description: Finalize validated work through safe Git gates.
---

# Finalize Git task

## Context and inputs
Require a validated trace, dedicated branch, reviewed diff, and separate explicit approvals for commit, push, and PR.

## Read
Read `AGENTS.md`, safe-git skill, active trace, policy, Git scripts, and complete diff.

## Procedure
Run the quality gate, verify OpenAPI and secrets, complete the trace, then use only safe commit, push, and PR scripts for approved operations.

## Validation and output
Record commit SHA, remote publication, PR URL, or exact blocker and final status.

## Safety
Refuse protected branches, failed checks, dirty unexpected files, missing approvals, force operations, and automatic merge.
