# DEV-002 — Canonical Institution, Campus & Geography Implementation

Status: Implemented in branch; pending merge.

This slice establishes the first canonical domain persistence layer from DB-001 and the approved governance documents.

## Included

- Institution identity foundation extended without replacing stable institution IDs.
- Institution names/aliases as a separate entity.
- Campus as a separate physical-presence entity.
- Geography as a hierarchical entity.
- Campus-to-geography relationship.
- Institution-to-campus relationship.
- Basic lifecycle/status fields.
- Migration-level integration test.

## Boundaries

This slice does not implement programs, offerings, admissions, provenance/evidence, verification workflows, search indexing, rankings, recommendations, or AI. Those remain separate implementation slices governed by their approved documents.
