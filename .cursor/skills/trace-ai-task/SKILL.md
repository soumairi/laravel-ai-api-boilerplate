---
name: trace-ai-task
description: Create and maintain a sanitized AI trace.
---

# Trace AI task

## Context and inputs
Use before repository changes with a sanitized purpose, acceptance criteria, agent identity, and approved plan.

## Read
Read `AGENTS.md`, `ai/policy.yml`, trace map, trace scripts, and current branch state.

## Procedure
Create a canonical ID, record sanitized scope and prompt hash when available, associate the dedicated branch, append files/commands/results under a lock, and move state through implementation and validation.

## Validation and output
Run the trace check and return the active ID, file, branch, and state.

## Safety
Never store raw prompts containing secrets, environment contents, credentials, tokens, keys, or personal data.
