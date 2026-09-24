# DEV-282 — Orphan / Missing Geography Detection

## Status

Implemented on branch `dev-282-geography-gap-detection`.

## Purpose

Provide a read-only detection boundary for active campuses whose canonical geography linkage is missing or inactive.

## Findings

- `missing_geography`: active campus has no canonical geography.
- `inactive_geography`: active campus references inactive canonical geography.
- Inactive campuses are excluded from this detection set.

## Safety

Detection never changes canonical data. Operator review and explicit correction remain separate workflows.
