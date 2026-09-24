# DEV-277 — Canonical Geography Linkage Integrity

## Status

Implemented on branch `dev-277-geography-integrity`.

## Purpose

Establish a read-only integrity boundary between active campuses and canonical geography.

## Rules

- An active campus should point to a canonical geography.
- The referenced geography must itself be active.
- Inactive campuses are not treated as public geography-integrity failures.
- The service reports findings; it does not silently repair canonical data.
- Operator review and explicit correction remain separate workflows.

## Scope

This slice does not change public visibility, perform automatic geography reassignment, or modify institution/campus status.
