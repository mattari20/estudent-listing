# DEV-004 — Provenance, Evidence & Verification Foundation

Status: Implemented in branch; pending merge.

This slice implements the first persistence layer for the governed chain:

Source → Evidence → Assertion → Verification State → Published Representation.

## Included

- Source records with source class and authority scope.
- Evidence records with references, excerpts, hashes and temporal metadata.
- Assertions tied to subjects and governed attributes.
- Verification state and confidence kept as separate concepts.
- Temporal validity fields.
- Material change events for operational history.

## Boundary

This does not yet implement automated verification workflows, moderation, source crawling, AI extraction, review queues, ranking inputs, or public provenance UI.

Those remain separate implementation slices.
