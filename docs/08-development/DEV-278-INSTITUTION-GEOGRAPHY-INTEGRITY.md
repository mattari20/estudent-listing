# DEV-278 — Institution–Geography Integrity Rules

## Status

Implemented on branch `dev-278-institution-geography-integrity`.

## Purpose

Define a read-only institution-level integrity boundary above campus-to-geography linkage.

## Rules

- Only active institutions are evaluated by this public-integrity boundary.
- An active institution should have at least one active campus.
- An active institution with active campuses should have at least one active campus linked to an active canonical geography.
- Missing or inactive geography coverage is reported as a finding; data is not silently repaired.
- Inactive institutions are outside this public-integrity finding set.

## Relationship to DEV-277

DEV-277 validates individual active campus-to-geography linkage.

DEV-278 evaluates the institution-level coverage implied by those campus relationships.

## Safety

This slice does not reassign campuses, activate geography, change institution/campus status, or alter public records.
Operator review and explicit correction remain separate workflows.
