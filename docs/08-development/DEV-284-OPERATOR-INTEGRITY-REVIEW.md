# DEV-284 — Operator Geography Integrity Review

## Status

Implemented on branch `dev-284-operator-integrity-review`.

## Purpose

Provide an authenticated operator-only, read-only view of institution and campus geography integrity findings.

## Access

The review route is inside the existing `auth` and `institute.operator` middleware boundary.

## Safety

- Public users cannot access the review route.
- The review page only displays findings.
- No repair, reassignment, activation, or deletion action is exposed.
- Corrections remain separate and auditable operator workflows.
