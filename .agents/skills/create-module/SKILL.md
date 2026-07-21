---
name: create-module
description: Create a complete modular API capability.
---

# Create module

## Context and inputs
Use for an approved business module. Require acceptance criteria, permissions, entities, and API operations.

## Read
Read `AGENTS.md`, local guides, `docs/create-module.md`, architecture, routes, and neighboring tests.

## Procedure
Design PostgreSQL schema, Domain rules, DTOs/Actions, Requests, Policy, Resource, thin controller, routes, audit, rate limits, tests, and Scramble contract.

## Validation and output
Run focused tests, Pint, PHPStan, OpenAPI synchronization, and the quality gate; report files and behavior.

## Safety
Preserve dependency direction, BOLA protections, existing contracts, and non-destructive migrations.
