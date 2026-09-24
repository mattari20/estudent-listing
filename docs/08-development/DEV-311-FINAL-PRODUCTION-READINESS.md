# DEV-311 — Final Production Readiness Gate

## Purpose

Create one final read-only operational gate that sits above promotion readiness and confirms that the latest source retrieval still has a complete retrieval lineage.

## Checks

Production readiness requires:

- import verification ready;
- source verification ready;
- provenance verification ready;
- the latest retrieval run is completed;
- the latest retrieval run has a source snapshot;
- the latest retrieval run has a content hash.

The latest retrieval run is intentionally selected by creation time so a newer failed run cannot be hidden by an older successful run.

## Route

GET /institutes/operations/imports/{importBatch}/final-readiness

## Safety

This endpoint is diagnostic only. It performs no promotion, canonical mutation, publication, or source mutation.
