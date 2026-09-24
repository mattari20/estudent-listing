# DEV-280 — Public Directory Geography Filtering

## Status

Implemented on branch `dev-280-public-geography-filtering`.

## Purpose

Ensure public institution geography filters resolve only through active campus and active canonical geography chains.

## Rules

- Public geography filters must use active campuses.
- The directly assigned campus geography must be active.
- Province filtering requires an active district and active province ancestor.
- District filtering requires an active district ancestor.
- City filtering requires an active city geography.
- Inactive geography ancestors must not make an institution publicly discoverable through a geography filter.
- This slice changes filtering behavior only; it does not mutate canonical records.

## Safety

The directory remains read-only. Geography correction, reassignment, activation, and operator review remain separate workflows.
