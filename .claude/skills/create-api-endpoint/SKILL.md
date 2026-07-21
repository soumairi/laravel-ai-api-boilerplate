---
name: create-api-endpoint
description: Add a secured versioned API endpoint.
---

# Create API endpoint

## Context and inputs
Require method, path, permission, validation, business behavior, and response contract.

## Read
Read `AGENTS.md`, `docs/create-api-endpoint.md`, routes, related Policy, Requests, Resources, and tests.

## Procedure
Load focused context; add a Form Request, DTO/Action when needed, object-aware Policy, Resource, thin controller method, named versioned route, rate limit, audit, tests, and Scramble metadata.

## Validation and output
Run focused behavior/security tests, export OpenAPI, compare routes, and update the trace.

## Safety
Never bypass authorization, accept unknown sensitive fields, expose models directly, or ship an undocumented endpoint.
