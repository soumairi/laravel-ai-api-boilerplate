---
name: bootstrap-project
description: Bootstrap this Laravel API safely with Docker.
---

# Bootstrap project

## Context and inputs
Use for first installation. Inputs are environment, Docker availability, and approved initialization scope.

## Read
Read `AGENTS.md`, `.env.example`, `compose.yaml`, `Dockerfile`, and `docs/getting-started.md`.

## Procedure
Inspect existing files, copy `.env.example` without exposing it, run `make install`, `make up`, and `make init`, then verify services.

## Validation and output
Report versions, service health, migrations, seeding, and OpenAPI initialization truthfully.

## Safety
Never overwrite `.env`, expose generated keys, run destructive migrations, or initialize production without explicit confirmation.
