---
name: sync-openapi
description: Export and verify Scramble OpenAPI contracts.
---

# Sync OpenAPI

## Context and inputs
Use after route, Request, Resource, enum, middleware, or response changes.

## Read
Read `AGENTS.md`, OpenAPI docs, routes, affected HTTP types, Scramble configuration/extensions, and OpenAPI tests.

## Procedure
Inspect route security and types, export Scramble, validate JSON, compare all API methods/paths, parameters, responses, and bearer security, then run OpenAPI tests.

## Validation and output
Report export path, OpenAPI version, operation count, tests, and synchronization status.

## Safety
Scramble is the only source; never hand-edit generated JSON or include secrets and sensitive examples.
