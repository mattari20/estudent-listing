# DEV-310 — Provenance Readiness Hardening

## Purpose

Close the remaining provenance gap between captured source evidence and controlled production promotion.

The readiness layer now verifies that candidate import rows have active evidence with usable snapshot lineage and complete assertions.

## Blocking rules

A candidate row blocks provenance readiness when:

- its active evidence has no captured source snapshot;
- the snapshot belongs to another source or is not captured;
- evidence and snapshot content hashes disagree; or
- no active assertion for the row contains both an attribute and value.

The final production promotion gate now requires provenance readiness in addition to import and source verification readiness.

## Safety

This batch is read-only. It does not mutate evidence, assertions, imports, canonical institutions, or publication state.
